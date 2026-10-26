<?php

namespace App\Services;

use App\Models\Media;
use App\Models\HeroSlide;
use App\Models\SiteSetting;

class Site
{
    public static function data(): array
    {
        $defaults = [];
        foreach (config('cms') as $group) {
            foreach ($group as $key => $field) {
                $defaults[$key] = $field[2];
            }
        }
        $data = array_replace($defaults, SiteSetting::pluck('value', 'key')->all());
        $preview = PageEditor::preview();
        if ($preview) $data = array_replace($data, $preview['settings']);
        if (($data['primary_url'] ?? null) === '/activities') {
            $data['primary_url'] = '/achievements';
            $data['primary_text'] = 'Explore achievements';
        }
        if (($data['stat_2_label'] ?? null) === 'Activities') {
            $data['stat_2_label'] = 'Programs';
        }
        unset($data['nav_activities'], $data['activities_link']);
        $images = Media::whereIn('id', array_filter([$data['logo_id'], $data['favicon_id'], $data['hero_image_id'] ?? null]))->get()->keyBy('id');
        $data['logo_url'] = isset($images[$data['logo_id']]) ? $images[$data['logo_id']]->url : asset('images/uecfi-logo.png');
        $data['favicon_url'] = isset($images[$data['favicon_id']]) ? $images[$data['favicon_id']]->url : $data['logo_url'];
        $slides = HeroSlide::with('media')->orderBy('sort_order')->orderBy('id')->get();
        $data['hero_slides'] = $slides->filter(fn ($slide) => $slide->media)->map(fn ($slide) => ['url' => $slide->media->url, 'video' => $slide->media->is_video, 'alt' => $slide->media->alt ?: $slide->media->name])->values()->all();
        if ($preview) {
            $media = Media::whereIn('id', $preview['slides'])->get()->keyBy('id');
            $data['hero_slides'] = collect($preview['slides'])->filter(fn($id)=>isset($media[$id]))->map(fn($id)=>['url'=>$media[$id]->url,'video'=>$media[$id]->is_video,'alt'=>$media[$id]->alt ?: $media[$id]->name])->values()->all();
        }
        $data['hero_images'] = collect($data['hero_slides'])->pluck('url')->all();
        $data['editor_css'] = PageEditor::css($preview['styles'] ?? PageEditor::read('visual_styles') ?? []);
        unset($data['visual_draft'], $data['visual_previous']);
        if ($data['hero_images'] === [] && ! empty($data['hero_image_id']) && isset($images[$data['hero_image_id']])) {
            $data['hero_images'] = [$images[$data['hero_image_id']]->url];
        }

        return $data;
    }
}
