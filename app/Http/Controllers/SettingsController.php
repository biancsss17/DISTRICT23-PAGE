<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\HeroSlide;
use App\Models\PageSection;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\MediaStorage;

class SettingsController extends Controller
{
    public function edit(string $group = 'home')
    {
        abort_unless(array_key_exists($group, config('cms')), 404);

        $selectedHeroIds = HeroSlide::orderBy('sort_order')->pluck('media_id')->all();
        if ($selectedHeroIds === [] && $legacyId = SiteSetting::where('key', 'hero_image_id')->value('value')) {
            $selectedHeroIds = [(int) $legacyId];
        }

        return view('admin.settings', [
            'group' => $group,
            'fields' => config('cms.'.$group),
            'media' => Media::latest()->get(),
            'sections' => PageSection::where('key', '!=', 'activities')->orderBy('sort_order')->get(),
            'selectedHeroIds' => $selectedHeroIds,
        ]);
    }

    public function update(Request $r, string $group)
    {
        abort_unless(array_key_exists($group, config('cms')), 404);
        $rules = [];
        foreach (config('cms.'.$group) as $key => $field) {
            $rules[$key] = match ($field[1]) {
                'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],'number' => 'required|integer|min:0|max:9999999',
                'media' => 'nullable|exists:media,id','email' => 'nullable|email|max:255','url' => 'nullable|url:http,https|max:255',
                'path' => ['required', 'string', 'max:255', function ($a, $v, $fail) {
                    if (! preg_match('~^/(?!/)[a-zA-Z0-9/_#?=&%.-]*$~', $v)) {
                        $fail('Use a local website path beginning with a single /.');
                    }
                }],
                default => in_array($key, ['hero_heading', 'hero_kicker', 'acronym', 'organization_name', 'primary_text', 'secondary_text', 'nav_home', 'nav_news', 'nav_achievements', 'nav_officers', 'nav_admin']) ? 'required|string|max:5000' : 'nullable|string|max:5000'
            };
        }
        if ($group === 'home') {
            $rules['hero_media'] = 'nullable|array|max:20';
            $rules['hero_media.*'] = 'required|integer|distinct|exists:media,id';
            $rules['hero_order'] = 'nullable|array';
            $rules['hero_order.*'] = 'nullable|integer|min:0|max:10000';
            $rules['hero_uploads'] = 'nullable|array|max:10';
            $rules['hero_uploads.*'] = 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg|max:51200';
        }
        $data = $r->validate($rules, [
            'hero_uploads.*.max' => 'Each homepage photo or video must be 50 MB or smaller.',
            'hero_uploads.max' => 'Choose up to 10 homepage slides at a time.',
        ]);
        $selected = $data['hero_media'] ?? [];
        $order = $data['hero_order'] ?? [];
        unset($data['hero_media'], $data['hero_order'], $data['hero_uploads']);
        $stored = [];
        try {
            DB::transaction(function () use ($data, $group, $r, $selected, $order, &$stored) {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
                if ($group === 'home') {
                    foreach ($r->file('hero_uploads', []) as $file) {
                        $media = app(MediaStorage::class)->store($file, $file->getClientOriginalName(), 'UECFI homepage cover image');
                        $stored[] = $media;
                        $selected[] = $media->id;
                    }
                    $selected = array_values(array_unique(array_map('intval', $selected)));
                    $position = [];
                    foreach ($selected as $index => $id) {
                        $position[$id] = (int) ($order[$id] ?? $index + 1);
                    }
                    usort($selected, fn ($a, $b) => ($position[$a] <=> $position[$b]) ?: ($a <=> $b));
                    HeroSlide::whereNotIn('media_id', $selected)->delete();
                    foreach ($selected as $index => $id) {
                        HeroSlide::updateOrCreate(['media_id' => $id], ['sort_order' => $index]);
                    }
                    SiteSetting::updateOrCreate(['key' => 'hero_image_id'], ['value' => null]);
                }
            });
        } catch (\Throwable $error) {
            foreach ($stored as $media) {
                app(MediaStorage::class)->delete($media);
            }
            throw $error;
        }

        return back()->with('success', 'Website updated successfully.');
    }

    public function sections(Request $r)
    {
        $data = $r->validate(['sections' => 'required|array', 'sections.*.id' => 'required|exists:page_sections,id', 'sections.*.title' => 'required|string|max:180', 'sections.*.description' => 'nullable|string|max:1000', 'sections.*.sort_order' => 'required|integer|min:0|max:1000', 'sections.*.visible' => 'nullable|boolean']);
        foreach ($data['sections'] as $section) {
            $section['visible'] = isset($section['visible']);
            PageSection::findOrFail($section['id'])->update($section);
        }

        return back()->with('success','Homepage sections updated.');
    }

}
