<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $centers = [
            [
                'name' => 'CENTRO UBBOG NI AYAT',
                'slug' => 'ubbog-ni-ayat',
                'address' => 'GANTE III STREET, PIONEER AVENUE, MAGUGPO WEST, TAGUM CITY, DAVAO DEL NORTE',
                'latitude' => 7.4545418,
                'longitude' => 125.7998692,
                'map_url' => 'https://www.google.com/maps/place/Ubbog+Ni+Ayat/@7.4545418,125.7998692,17z',
                'sort_order' => 0,
            ],
            [
                'name' => 'CENTRO WISDOM',
                'slug' => 'centro-wisdom',
                'address' => '',
                'latitude' => 7.3618161,
                'longitude' => 125.704352,
                'map_url' => 'https://www.google.com/maps/place/UECFI+-+Centro+Wisdom+(District+23)/@7.3618161,125.704352,17z',
                'sort_order' => 1,
            ],
            [
                'name' => 'CENTRO TEMPLE OF JESUS CHRIST - THE LIVING GOD',
                'slug' => 'temple-of-jesus-christ-the-living-god',
                'address' => '',
                'latitude' => 7.1081749,
                'longitude' => 125.6125766,
                'map_url' => 'https://www.google.com/maps/place/UECFI,+Temple+of+Jesus+Christ/@7.1081749,125.6125766,17z',
                'sort_order' => 2,
            ],
        ];

        foreach ($centers as $center) {
            DB::table('centers')->updateOrInsert(
                ['slug' => $center['slug']],
                array_merge($center, [
                    'description' => null,
                    'published_at' => $now,
                    'status' => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }
    }

    public function down(): void
    {
        DB::table('centers')->whereIn('slug', [
            'ubbog-ni-ayat',
            'centro-wisdom',
            'temple-of-jesus-christ-the-living-god',
        ])->delete();
    }
};
