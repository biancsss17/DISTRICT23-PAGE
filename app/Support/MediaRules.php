<?php

namespace App\Support;

use App\Rules\MediaUploadSize;

class MediaRules
{
    public const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg'];

    public static function upload(bool $required = false, bool $photoOnly = false): array
    {
        $extensions = $photoOnly
            ? self::PHOTO_EXTENSIONS
            : [...self::PHOTO_EXTENSIONS, ...self::VIDEO_EXTENSIONS];

        return [
            $required ? 'required' : 'nullable',
            $photoOnly ? 'image' : 'file',
            'mimes:'.implode(',', $extensions),
            new MediaUploadSize,
        ];
    }

    public static function isVideoExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::VIDEO_EXTENSIONS, true);
    }

    public static function limits(): array
    {
        return [
            'photoMb' => config('media.max_photo_kb') / 1024,
            'videoMb' => config('media.max_video_kb') / 1024,
            'maxPhotos' => config('media.max_photos_per_item'),
            'maxVideos' => config('media.max_videos_per_item'),
            'videoExtensions' => self::VIDEO_EXTENSIONS,
        ];
    }
}
