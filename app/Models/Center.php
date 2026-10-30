<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'published_at' => 'datetime'];
    }

    public function getMapEmbedUrlAttribute(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $lat = $this->latitude;
        $lng = $this->longitude;
        $delta = 0.006;

        return 'https://www.openstreetmap.org/export/embed.html?bbox=' . implode(',', [
            $lng - $delta, $lat - $delta, $lng + $delta, $lat + $delta,
        ]) . '&layer=mapnik&marker=' . $lat . ',' . $lng;
    }
}
