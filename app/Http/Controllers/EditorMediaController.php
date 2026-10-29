<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\MediaStorage;
use App\Services\PageEditor;
use App\Support\MediaRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditorMediaController extends Controller
{
    public function store(Request $request, MediaStorage $storage)
    {
        $request->validate(['file' => MediaRules::upload(required: true)]);
        $file = $request->file('file');
        $media = $storage->store($file, $file->getClientOriginalName());

        return response()->json(['id' => $media->id, 'name' => $media->name, 'url' => $media->url, 'video' => $media->is_video]);
    }

    public function destroy(Media $media)
    {
        DB::transaction(function () use ($media) {
            SiteSetting::whereIn('key', ['logo_id', 'favicon_id', 'hero_image_id'])->where('value', (string) $media->id)->update(['value' => null]);
            $draft = PageEditor::read('visual_draft');
            if ($draft) {
                $draft['page']['slides'] = array_values(array_filter($draft['page']['slides'], fn ($id) => (int) $id !== $media->id));
                foreach (['logo_id', 'favicon_id'] as $key) {
                    if ((int) ($draft['page']['settings'][$key] ?? 0) === $media->id) {
                        $draft['page']['settings'][$key] = null;
                    }
                }
                PageEditor::write('visual_draft', $draft);
            }
            $media->delete();
            // Keep database references when the storage provider rejects deletion.
            app(MediaStorage::class)->delete($media);
        });

        return response()->json(['message' => $media->is_video ? 'Video deleted.' : 'Photo deleted.']);
    }
}
