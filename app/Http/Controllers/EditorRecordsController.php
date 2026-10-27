<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Media;
use App\Models\Officer;
use App\Models\SiteSetting;
use App\Rules\MediaUploadSize;
use App\Services\MediaStorage;
use App\Services\PageEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditorRecordsController extends Controller
{
    private function checkType(string $type): void
    {
        abort_unless(in_array($type, ['news', 'achievements', 'officers'], true), 404);
    }

    private function media(?Media $item): ?array
    {
        return $item ? ['id' => $item->id, 'name' => $item->name, 'url' => $item->url, 'video' => $item->is_video] : null;
    }

    private function item(Content|Officer $record): array
    {
        if ($record instanceof Officer) {
            $record->load(['photo', 'gallery']);
            return [
                'id' => $record->id, 'name' => $record->name, 'position' => $record->position,
                'term' => $record->term, 'biography' => $record->biography, 'email' => $record->email,
                'social_url' => $record->social_url, 'sort_order' => $record->sort_order, 'status' => $record->status,
                'cover' => $this->media($record->photo), 'gallery' => $record->gallery->map(fn ($media) => $this->media($media))->all(),
            ];
        }
        $record->load(['cover', 'gallery']);
        return [
            'id' => $record->id, 'title' => $record->title, 'slug' => $record->slug,
            'excerpt' => $record->excerpt, 'content' => $record->content, 'category' => $record->category,
            'published_at' => $record->published_at?->format('Y-m-d\TH:i'), 'event_date' => $record->event_date?->format('Y-m-d'),
            'status' => $record->status, 'featured' => $record->featured, 'sort_order' => $record->sort_order,
            'cover' => $this->media($record->cover), 'gallery' => $record->gallery->map(fn ($media) => $this->media($media))->all(),
        ];
    }

    public function index(string $type)
    {
        $this->checkType($type);
        $records = $type === 'officers'
            ? Officer::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'position', 'status', 'sort_order'])
            : Content::where('type', $type)->orderBy('sort_order')->latest('updated_at')->get(['id', 'title', 'status', 'sort_order', 'featured']);
        return response()->json(['records' => $records]);
    }

    public function show(string $type, int $id)
    {
        $this->checkType($type);
        $record = $type === 'officers' ? Officer::findOrFail($id) : Content::where('type', $type)->findOrFail($id);
        return response()->json(['record' => $this->item($record)]);
    }

    public function preview(string $type, int $id)
    {
        abort_unless(in_array($type, ['news', 'achievements'], true), 404);
        $record = Content::with(['cover', 'gallery'])->where('type', $type)->findOrFail($id);
        $related = Content::with('cover')->published()->where('type', $type)->where('id', '!=', $id)->limit(3)->get();
        return response()->view('public.detail', ['record' => $record, 'related' => $related, 'type' => $type, 'preview' => true])
            ->header('Cache-Control', 'no-store');
    }

    public function save(Request $request, string $type, ?int $id = null)
    {
        $this->checkType($type);
        if ($type === 'officers') {
            $record = $id ? Officer::findOrFail($id) : new Officer;
            return $this->saveOfficer($request, $record);
        }
        $record = $id ? Content::where('type', $type)->findOrFail($id) : new Content;
        return $this->saveContent($request, $type, $record);
    }

    private function saveContent(Request $request, string $type, Content $record)
    {
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'slug' => ['required', 'alpha_dash', 'max:180', Rule::unique('contents')->ignore($record->id)],
            'excerpt' => 'nullable|string|max:600', 'content' => 'required|string|max:100000',
            'category' => 'nullable|string|max:80', 'published_at' => 'nullable|date|required_if:status,published',
            'event_date' => 'nullable|date', 'status' => 'required|in:draft,published',
            'featured' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:10000',
            'cover_upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg', new MediaUploadSize],
            'gallery_uploads' => 'nullable|array|max:4',
            'gallery_uploads.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg', new MediaUploadSize],
        ]);
        $galleryFiles = $request->file('gallery_uploads', []);
        $kinds = collect($galleryFiles)->map(fn ($file) => $this->kind($file->getClientOriginalExtension()))
            ->concat($request->hasFile('gallery_uploads') ? [] : $record->gallery->map(fn ($media) => $media->is_video ? 'video' : 'photo'));
        $cover = $request->file('cover_upload');
        if ($cover) $kinds->push($this->kind($cover->getClientOriginalExtension()));
        elseif ($record->cover) $kinds->push($record->cover->is_video ? 'video' : 'photo');
        $this->checkMediaLimits($kinds);
        unset($data['cover_upload'], $data['gallery_uploads']);
        $data['featured'] = $request->boolean('featured');
        $data['type'] = $type;
        $stored = [];
        $storage = app(MediaStorage::class);
        try {
            DB::transaction(function () use ($request, $record, $data, $cover, $galleryFiles, $storage, &$stored) {
                if ($cover) {
                    $media = $storage->store($cover, $cover->getClientOriginalName(), $data['title']);
                    $stored[] = $media;
                    $data['cover_id'] = $media->id;
                }
                $record->fill($data);
                if (! $record->exists) $record->created_by = $request->user()->id;
                $record->save();
                if ($request->hasFile('gallery_uploads')) {
                    $ids = [];
                    foreach ($galleryFiles as $file) {
                        $media = $storage->store($file, $file->getClientOriginalName(), $data['title']);
                        $stored[] = $media;
                        $ids[] = $media->id;
                    }
                    $record->gallery()->sync(collect($ids)->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all());
                }
            });
        } catch (\Throwable $error) {
            foreach ($stored as $media) $storage->delete($media);
            throw $error;
        }
        return response()->json(['message' => ucfirst($type).' saved.', 'record' => $this->item($record)]);
    }

    private function saveOfficer(Request $request, Officer $record)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160', 'position' => 'required|string|max:160',
            'term' => 'nullable|string|max:160', 'biography' => 'nullable|string|max:10000',
            'email' => 'nullable|email|max:255', 'social_url' => 'nullable|url:http,https|max:255',
            'sort_order' => 'required|integer|min:0|max:10000', 'status' => 'required|in:draft,published',
            'photo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', new MediaUploadSize],
            'gallery_uploads' => 'nullable|array|max:4',
            'gallery_uploads.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg', new MediaUploadSize],
        ]);
        $galleryFiles = $request->file('gallery_uploads', []);
        $kinds = collect($galleryFiles)->map(fn ($file) => $this->kind($file->getClientOriginalExtension()))
            ->concat($request->hasFile('gallery_uploads') ? [] : $record->gallery->map(fn ($media) => $media->is_video ? 'video' : 'photo'));
        if ($request->hasFile('photo_upload') || $record->photo_id) $kinds->push('photo');
        $this->checkMediaLimits($kinds);
        unset($data['photo_upload'], $data['gallery_uploads']);
        $stored = [];
        $storage = app(MediaStorage::class);
        try {
            DB::transaction(function () use ($request, $record, $data, $galleryFiles, $storage, &$stored) {
                $record->fill($data)->save();
                if ($request->hasFile('photo_upload')) {
                    $portrait = $storage->store($request->file('photo_upload'), 'Officer portrait', $data['name']);
                    $stored[] = $portrait;
                    $record->photo_id = $portrait->id;
                    $record->save();
                }
                if ($request->hasFile('gallery_uploads')) {
                    $ids = [];
                    foreach ($galleryFiles as $file) {
                        $media = $storage->store($file, $file->getClientOriginalName(), $data['name']);
                        $stored[] = $media;
                        $ids[] = $media->id;
                    }
                    $record->gallery()->sync(collect($ids)->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all());
                }
            });
        } catch (\Throwable $error) {
            foreach ($stored as $media) $storage->delete($media);
            throw $error;
        }
        return response()->json(['message' => 'Officer saved.', 'record' => $this->item($record)]);
    }

    private function kind(string $extension): string
    {
        return in_array(strtolower($extension), ['mp4', 'webm', 'ogg'], true) ? 'video' : 'photo';
    }

    private function checkMediaLimits($kinds): void
    {
        if ($kinds->filter(fn ($kind) => $kind === 'photo')->count() > config('media.max_photos_per_item', 3))
            throw ValidationException::withMessages(['gallery_uploads' => 'Use at most 3 photos total, including the cover or portrait.']);
        if ($kinds->filter(fn ($kind) => $kind === 'video')->count() > config('media.max_videos_per_item', 1))
            throw ValidationException::withMessages(['gallery_uploads' => 'Use at most 1 video total.']);
    }

    public function destroy(string $type, int $id)
    {
        $this->checkType($type);
        $record = $type === 'officers' ? Officer::findOrFail($id) : Content::where('type', $type)->findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'Record deleted.']);
    }

    public function destroyMedia(Media $media)
    {
        DB::transaction(function () use ($media) {
            SiteSetting::whereIn('key', ['logo_id', 'favicon_id', 'hero_image_id'])->where('value', (string) $media->id)->update(['value' => null]);
            $draft = PageEditor::read('visual_draft');
            if ($draft) {
                $draft['page']['slides'] = array_values(array_filter($draft['page']['slides'], fn ($id) => (int) $id !== $media->id));
                foreach (['logo_id', 'favicon_id'] as $key)
                    if ((int) ($draft['page']['settings'][$key] ?? 0) === $media->id) $draft['page']['settings'][$key] = null;
                PageEditor::write('visual_draft', $draft);
            }
            $media->delete();
            // Keep database references when the storage provider rejects deletion.
            app(MediaStorage::class)->delete($media);
        });
        return response()->json(['message' => $media->is_video ? 'Video deleted.' : 'Photo deleted.']);
    }

}
