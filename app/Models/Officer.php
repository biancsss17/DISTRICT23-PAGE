<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Officer extends Model
{
    protected $guarded = ['id'];

    public function photo()
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    public function gallery()
    {
        return $this->belongsToMany(Media::class, 'officer_media')->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
