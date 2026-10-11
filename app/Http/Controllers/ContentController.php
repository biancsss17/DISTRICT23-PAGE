<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\MediaStorage;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index(string $type)
    {
        $records = Content::where('type', $type)->latest()->paginate(20);

        return view('admin.contents', compact('records', 'type'));
    }

    public function create(string $type)
    {
        return view('admin.content-form', ['record' => new Content, 'type' => $type]);
    }

    public function edit(string $type, Content $record)
    {
        abort_unless($record->type === $type, 404);

        return view('admin.content-form', ['record' => $record->load('gallery'), 'type' => $type]);
    }

    public function store(Request $r, string $type)
    {
        return $this->save($r, $type, new Content);
    }

    public function update(Request $r, string $type, Content $record)
    {
        abort_unless($record->type === $type, 404);

        return $this->save($r, $type, $record);
    }

    private function save(Request $r, string $type, Content $record)
    {
        $data = $r->validate([
            'title' => 'required|string|max:180', 'slug' => ['required', 'alpha_dash', 'max:180', Rule::unique('contents')->ignore($record->id)],
            'excerpt' => 'nullable|string|max:600', 'content' => 'required|string|max:100000', 'category' => 'nullable|string|max:80',
            'cover_id' => 'nullable|exists:media,id', 'cover_upload' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg|max:51200', 'published_at' => 'nullable|date|required_if:status,published', 'event_date' => 'nullable|date',
            'location' => 'nullable|string|max:255', 'status' => 'required|in:draft,published', 'featured' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:10000',
            'gallery_uploads' => 'nullable|array|max:4', 'gallery_uploads.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg|max:51200',
        ], [
            'cover_upload.max' => 'The cover file must be 50 MB or smaller.',
            'gallery_uploads.*.max' => 'Each photo or video must be 50 MB or smaller.',
            'gallery_uploads.max' => 'Choose no more than 4 slideshow files; the story limit is 3 photos and 1 video total, including the cover.',
        ]);
        $galleryFiles = $r->file('gallery_uploads', []);
        $coverFile = $r->file('cover_upload');
        $currentGallery = $record->exists && ! $r->hasFile('gallery_uploads') ? $record->gallery : collect();
        $currentCover = $record->exists ? $record->cover : null;
        $coverId = $data['cover_id'] ?? $record->cover_id;
        $coverIsReplaced = $coverFile || ($r->exists('cover_id') && (string) $coverId !== (string) $record->cover_id);
        $coverMedia = $coverFile ? null : ($coverIsReplaced ? Media::find($coverId) : $currentCover);
        $mediaKinds = collect($galleryFiles)->map(fn ($file) => in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'webm', 'ogg'], true) ? 'video' : 'photo')
            ->concat($currentGallery->map(fn ($media) => $media->is_video ? 'video' : 'photo'));
        if ($coverFile) {
            $mediaKinds->push(in_array(strtolower($coverFile->getClientOriginalExtension()), ['mp4', 'webm', 'ogg'], true) ? 'video' : 'photo');
        } elseif ($coverMedia) {
            $mediaKinds->push($coverMedia->is_video ? 'video' : 'photo');
        }
        $maxPhotos = config('media.max_photos_per_item', 3);
        $maxVideos = config('media.max_videos_per_item', 1);
        if ($mediaKinds->filter(fn ($kind) => $kind === 'photo')->count() > $maxPhotos) {
            throw \Illuminate\Validation\ValidationException::withMessages(['gallery_uploads' => "Each content item can have up to {$maxPhotos} photos total, including its cover. Remove a photo or choose fewer uploads."]);
        }
        if ($mediaKinds->filter(fn ($kind) => $kind === 'video')->count() > $maxVideos) {
            throw \Illuminate\Validation\ValidationException::withMessages(['gallery_uploads' => "Each content item can have up to {$maxVideos} video total, including its cover. Remove a video or choose fewer uploads."]);
        }
        $storedPaths = [];
        $gallery = [];
        unset($data['cover_upload'], $data['gallery_uploads']);
        $data['featured'] = $r->boolean('featured');
        $data['type'] = $type;
        $mediaStorage = app(MediaStorage::class);
        try { DB::transaction(function () use ($record, $data, &$gallery, $r, $type, &$storedPaths, $mediaStorage) {
            if ($r->hasFile('cover_upload')) {
                $cover = $mediaStorage->store($r->file('cover_upload'), 'Cover image', $data['title']);
                $storedPaths[] = $cover;
                $data['cover_id'] = $cover->id;
            }
            $record->fill($data);
            if (! $record->exists) {
                $record->created_by = $r->user()->id;
            } $record->save();
            if ($r->hasFile('gallery_uploads')) {
                foreach ($r->file('gallery_uploads') as $file) {
                    $media = $mediaStorage->store($file, $file->getClientOriginalName(), $record->title);
                    $storedPaths[] = $media;
                    $gallery[] = $media->id;
                }
                $record->gallery()->sync(collect($gallery)->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
            }
        });
        } catch (\Throwable $error) { foreach ($storedPaths as $media) { if ($media instanceof Media) $mediaStorage->delete($media); } throw $error; }

        return redirect()->route('admin.content.edit', [$type, $record])->with('success', ucfirst($type).' saved.');
    }

    public function destroy(string $type, Content $record)
    {
        abort_unless($record->type === $type, 404);
        $record->delete();

        return redirect()->route('admin.content.index', $type)->with('success', 'Content deleted.');
    }

    public function preview(string $type, Content $record)
    {
        abort_unless($record->type === $type, 404);
        $record->load('cover', 'gallery');

        return view('public.detail',['record' => $record, 'type' => $type, 'related' => collect(), 'preview' => true]);
    }
}
