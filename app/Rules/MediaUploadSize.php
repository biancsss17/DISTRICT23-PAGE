<?php

namespace App\Rules;

use App\Support\MediaRules;
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
        $videoExtension = MediaRules::isVideoExtension($extension);
        $video = str_starts_with($mime, 'video/') || ($videoExtension && ! str_starts_with($mime, 'image/'));
        $limitKb = (int) config($video ? 'media.max_video_kb' : 'media.max_photo_kb');

        if ($value->getSize() > $limitKb * 1024) {
            $kind = $video ? 'Videos' : 'Photos';
            $limitMb = $limitKb / 1024;
            $fail("{$kind} must be {$limitMb} MB or smaller.");
        }
    }
}
