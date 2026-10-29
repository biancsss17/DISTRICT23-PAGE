<?php

namespace App\Models;

use App\Support\MediaRules;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    protected $attributes = ['disk' => 'public'];

    public function getStorageDiskAttribute(): string
    {
        return $this->disk ?: 'public';
    }

    public function getUrlAttribute()
    {
        return route('media.show', $this);
    }

    public function getIsVideoAttribute(): bool
    {
        return MediaRules::isVideoExtension(pathinfo($this->path, PATHINFO_EXTENSION));
    }
}
