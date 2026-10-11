<?php

return [
    // Existing media remains on its recorded disk; new uploads use this disk.
    'disk' => env('CMS_MEDIA_DISK', 'public'),
    'max_file_kb' => 51200,
    'max_photos_per_item' => 3,
    'max_videos_per_item' => 1,
];
