<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Officer;
use App\Models\PageSection;
use App\Services\PageEditor;

class PublicController extends Controller
{
    public function home()
    {
        $sections = PageSection::where('visible', true)->where('key', '!=', 'activities')->orderBy('sort_order')->get();
        if ($preview = PageEditor::preview()) {
            $sections = PageSection::where('key', '!=', 'activities')->get()->map(function ($section) use ($preview) {
                $draft = collect($preview['sections'])->firstWhere('id', $section->id);
                if ($draft) {
                    $section->fill(array_intersect_key($draft, array_flip(['title', 'description', 'sort_order', 'visible'])));
                }

                return $section;
            })->filter(fn ($section) => $section->visible)->sortBy('sort_order')->values();
        }
        $featured = collect(['news', 'achievements'])->mapWithKeys(fn ($type) => [$type => Content::with('cover')->published()->where('type', $type)->orderByDesc('featured')->orderBy('sort_order')->latest('published_at')->limit(3)->get()]);
        $officers = Officer::with(['photo', 'gallery'])->where('status', 'published')->orderBy('sort_order')->orderBy('name')->limit(4)->get();

        return view('public.home', compact('sections', 'featured', 'officers'));
    }

    public function index(string $type)
    {
        $records = Content::with('cover')->published()->where('type', $type)->orderByDesc('featured')->orderBy('sort_order')->latest('published_at')->paginate(9);
        $section = PageSection::where('key', $type)->firstOrFail();

        return view('public.list', compact('records', 'type', 'section'));
    }

    public function show(string $type, string $slug)
    {
        $record = Content::with('cover', 'gallery')->published()->where('type', $type)->where('slug', $slug)->firstOrFail();
        $related = Content::with('cover')->published()->where('type', $type)->where('id', '!=', $record->id)->limit(3)->get();

        return view('public.detail', compact('record', 'type', 'related'));
    }

    public function officers()
    {
        $officers = Officer::with(['photo', 'gallery'])->where('status', 'published')->orderBy('sort_order')->orderBy('name')->get();
        $section = PageSection::where('key', 'officers')->firstOrFail();

        return view('public.officers', compact('officers', 'section'));
    }
}
