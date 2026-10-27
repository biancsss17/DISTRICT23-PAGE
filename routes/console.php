<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('media:check-neon', function () {
    $required = ['key', 'secret', 'region', 'bucket', 'endpoint'];
    $missing = array_filter($required, fn ($field) => blank(config('filesystems.disks.neon.'.$field)));
    if ($missing) {
        $this->error('Neon storage is not configured. Add these Railway variables first: '.implode(', ', array_map(fn ($field) => match ($field) {
            'key' => 'NEON_STORAGE_ACCESS_KEY_ID',
            'secret' => 'NEON_STORAGE_SECRET_ACCESS_KEY',
            'region' => 'NEON_STORAGE_REGION',
            'bucket' => 'NEON_STORAGE_BUCKET',
            'endpoint' => 'NEON_STORAGE_ENDPOINT',
        }, $missing)));
        return 1;
    }

    $disk = Storage::disk('neon');
    $path = 'cms/.neon-check-'.Str::uuid().'.txt';
    $contents = 'D23 Neon storage connection check';
    try {
        if (! $disk->put($path, $contents) || $disk->get($path) !== $contents) {
            throw new \RuntimeException('Neon storage could not read back the test object.');
        }
        if (! $disk->delete($path)) {
            throw new \RuntimeException('Neon storage could not delete the test object.');
        }
    } catch (\Throwable) {
        try {
            $disk->delete($path);
        } catch (\Throwable) {
            // Leave the original check failure as the visible result.
        }
        $this->error('Neon storage write/read/delete check failed. Verify the bucket, branch endpoint, region and server-side credentials.');
        return 1;
    }

    $this->info('Neon storage write/read/delete check passed. New uploads can now use CMS_MEDIA_DISK=neon.');
    return 0;
})->purpose('Check the Neon media bucket without changing the active upload disk');
