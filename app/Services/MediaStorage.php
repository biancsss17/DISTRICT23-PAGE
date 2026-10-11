<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public function store(UploadedFile $file, string $name, ?string $alt = null): Media
    {
        $disk = config('media.disk', 'public');
        $path = $file->store('cms', $disk);

        if (! $path) {
            throw new \RuntimeException('The media file could not be stored. Check the configured media storage.');
        }

        return Media::create([
            'disk' => $disk,
            'path' => $path,
            'name' => mb_substr($name, 0, 255),
            'alt' => $alt,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->storage_disk)->delete($media->path);
    }
}
