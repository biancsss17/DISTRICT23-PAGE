<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('media:check-r2', function () {
    $required = ['key', 'secret', 'bucket', 'endpoint'];
    $missing = array_filter($required, fn ($field) => blank(config('filesystems.disks.r2.'.$field)));
    if ($missing) {
        $this->error('R2 is not configured. Add these Railway variables first: '.implode(', ', array_map(fn ($field) => match ($field) {
            'key' => 'R2_ACCESS_KEY_ID',
            'secret' => 'R2_SECRET_ACCESS_KEY',
            'bucket' => 'R2_BUCKET',
            'endpoint' => 'R2_ENDPOINT',
        }, $missing)));
        return 1;
    }

    $disk = Storage::disk('r2');
    $path = 'cms/.r2-check-'.Str::uuid().'.txt';
    $contents = 'D23 R2 connection check';
    try {
        if (! $disk->put($path, $contents) || $disk->get($path) !== $contents) {
            throw new \RuntimeException('R2 could not read back the test object.');
        }
        if (! $disk->delete($path)) {
            throw new \RuntimeException('R2 could not delete the test object.');
        }
    } catch (\Throwable) {
        try {
            $disk->delete($path);
        } catch (\Throwable) {
            // Leave the original check failure as the visible result.
        }
        $this->error('R2 write/read/delete check failed. Verify the bucket, endpoint and bucket-scoped Object Read & Write token.');
        return 1;
    }

    $this->info('R2 write/read/delete check passed. New uploads can now use CMS_MEDIA_DISK=r2.');
    return 0;
})->purpose('Check the R2 media bucket without changing the active upload disk');
