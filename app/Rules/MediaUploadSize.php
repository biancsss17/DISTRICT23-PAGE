<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class MediaUploadSize implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = $value->getMimeType() ?: '';
        $videoExtension = in_array($extension, ['mp4', 'webm', 'ogg'], true);
        $video = str_starts_with($mime, 'video/') || ($videoExtension && ! str_starts_with($mime, 'image/'));
        $limitKb = (int) config($video ? 'media.max_video_kb' : 'media.max_photo_kb');

        if ($value->getSize() > $limitKb * 1024) {
            $fail($video ? 'Videos must be 50 MB or smaller.' : 'Photos must be 10 MB or smaller.');
        }
    }
}
