<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function show(Media $media)
    {
        $disk = Storage::disk($media->storage_disk);
        abort_unless($disk->exists($media->path), 404);

        if ($media->storage_disk === 'public') {
            return response()->file($disk->path($media->path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
        }

        return $disk->response($media->path, $media->name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }
}
