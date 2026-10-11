<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\MediaStorage;

class OfficerController extends Controller
{
    public function index()
    {
        return view('admin.officers', ['officers' => Officer::with(['photo', 'gallery'])->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.officer-form', ['officer' => new Officer]);
    }

    public function edit(Officer $officer)
    {
        return view('admin.officer-form', ['officer' => $officer->load(['photo', 'gallery'])]);
    }

    public function store(Request $r)
    {
        return $this->save($r, new Officer);
    }

    public function update(Request $r, Officer $officer)
    {
        return $this->save($r, $officer);
    }

    private function save(Request $r, Officer $officer)
    {
        $data = $r->validate([
            'name' => 'required|string|max:160', 'position' => 'required|string|max:160', 'term' => 'nullable|string|max:160',
            'biography' => 'nullable|string|max:10000', 'email' => 'nullable|email|max:255', 'social_url' => 'nullable|url:http,https|max:255',
            'photo_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:51200',
            'gallery_uploads' => 'nullable|array|max:4', 'gallery_uploads.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg|max:51200',
            'sort_order' => 'required|integer|min:0|max:10000', 'status' => 'required|in:draft,published',
        ], [
            'photo_upload.max' => 'The portrait must be 50 MB or smaller.',
            'gallery_uploads.*.max' => 'Each photo or video must be 50 MB or smaller.',
            'gallery_uploads.max' => 'Choose no more than 4 additional media files; the officer limit is 3 photos total and 1 video.',
        ]);
        $galleryFiles = $r->file('gallery_uploads', []);
        $gallery = $officer->exists && ! $r->hasFile('gallery_uploads') ? $officer->gallery : collect();
        $kinds = collect($galleryFiles)->map(fn ($file) => in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'webm', 'ogg'], true) ? 'video' : 'photo')
            ->concat($gallery->map(fn ($media) => $media->is_video ? 'video' : 'photo'));
        if ($r->hasFile('photo_upload') || $officer->photo_id) $kinds->push('photo');
        if ($kinds->filter(fn ($kind) => $kind === 'photo')->count() > config('media.max_photos_per_item', 3)) {
            throw ValidationException::withMessages(['gallery_uploads' => 'An officer profile can have up to 3 photos total, including the portrait. Remove a photo or choose fewer uploads.']);
        }
        if ($kinds->filter(fn ($kind) => $kind === 'video')->count() > config('media.max_videos_per_item', 1)) {
            throw ValidationException::withMessages(['gallery_uploads' => 'An officer profile can have up to 1 video. Remove the existing video or choose fewer uploads.']);
        }

        unset($data['photo_upload'], $data['gallery_uploads']);
        $mediaStorage = app(MediaStorage::class);
        $stored = [];
        try {
            DB::transaction(function () use ($r, $officer, $data, $galleryFiles, $mediaStorage, &$stored) {
                $officer->fill($data)->save();
                if ($r->hasFile('photo_upload')) {
                    $portrait = $mediaStorage->store($r->file('photo_upload'), 'Officer portrait', $data['name']);
                    $stored[] = $portrait;
                    $officer->photo_id = $portrait->id;
                    $officer->save();
                }
                if ($r->hasFile('gallery_uploads')) {
                    $ids = [];
                    foreach ($galleryFiles as $file) {
                        $media = $mediaStorage->store($file, $file->getClientOriginalName(), $data['name']);
                        $stored[] = $media;
                        $ids[] = $media->id;
                    }
                    $officer->gallery()->sync(collect($ids)->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all());
                }
            });
        } catch (\Throwable $error) {
            foreach ($stored as $media) $mediaStorage->delete($media);
            throw $error;
        }

        return redirect()->route('admin.officers.edit', $officer)->with('success', 'Officer saved.');
    }

    public function destroy(Officer $officer)
    {
        $officer->delete();

        return back()->with('success', 'Officer deleted.');
    }
}
