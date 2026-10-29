<?php

namespace App\Services;

use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

class PageDrafts
{
    public function save(array $data): string
    {
        $existing = PageEditor::read('visual_draft');
        abort_if(($existing['revision'] ?? '') !== ($data['revision'] ?? ''), 409, 'This draft changed in another window. Reload before editing.');
        unset($data['revision']);
        $keys = collect(config('cms'))->flatMap(fn ($f) => array_keys($f))->all();
        $data['settings'] = array_intersect_key($data['settings'], array_flip($keys));
        $data['styles'] = array_intersect_key($data['styles'] ?? [], array_flip([...array_keys(config('page_editor.targets')), 'background', 'spacing']));
        $data['sections'] = array_map(fn ($s) => array_intersect_key($s, array_flip(['id', 'title', 'description', 'sort_order', 'visible'])), $data['sections']);
        $sectionKeys = PageSection::where('key', '!=', 'activities')->pluck('key', 'id');
        abort_unless(count($data['sections']) === $sectionKeys->count(), 422, 'Include all homepage sections.');
        $data['sections'] = array_map(function (array $section) use ($sectionKeys) {
            $section['key'] = $sectionKeys->get($section['id']);

            return $section;
        }, $data['sections']);
        $revision = bin2hex(random_bytes(16));
        PageEditor::write('visual_draft', ['page' => $data, 'revision' => $revision, 'base' => $existing['base'] ?? PageEditor::fingerprint(PageEditor::current())]);

        return $revision;
    }

    private function apply(array $page): void
    {
        foreach ($page['settings'] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        PageEditor::write('visual_styles', $page['styles']);
        foreach ($page['sections'] as $section) {
            $id = $section['id'];
            unset($section['id'],$section['key']);
            PageSection::whereKey($id)->where('key', '!=', 'activities')->update($section);
        }
        HeroSlide::query()->delete();
        foreach ($page['slides'] as $order => $id) {
            HeroSlide::create(['media_id' => $id, 'sort_order' => $order]);
        }
        SiteSetting::updateOrCreate(['key' => 'hero_image_id'], ['value' => null]);
    }

    public function publish(mixed $revision): void
    {
        $draft = PageEditor::read('visual_draft');
        abort_unless($draft, 422, 'Save a draft first.');
        abort_unless($revision === $draft['revision'], 409, 'Draft changed. Reload before publishing.');
        abort_unless($draft['base'] === PageEditor::fingerprint(PageEditor::current()), 409, 'Public settings changed outside this editor. Reset the draft to the current page before continuing.');
        abort_unless(Media::whereIn('id', $draft['page']['slides'])->count() === count($draft['page']['slides']), 422, 'A selected slide was deleted. Update the media selection.');
        DB::transaction(function () use ($draft) {
            PageEditor::write('visual_previous', PageEditor::current());
            $this->apply($draft['page']);
            SiteSetting::where('key', 'visual_draft')->delete();
        });
    }

    public function discard(): void
    {
        SiteSetting::where('key', 'visual_draft')->delete();
    }

    public function restore(): void
    {
        $page = PageEditor::read('visual_previous');
        abort_unless($page, 422, 'No previous publication is available.');
        abort_unless(Media::whereIn('id', $page['slides'])->count() === count($page['slides']), 422, 'Previous media was deleted. Restore those files before restoring this version.');
        DB::transaction(function () use ($page) {
            $this->apply($page);
            SiteSetting::where('key','visual_draft')->delete();
        });
    }
}
