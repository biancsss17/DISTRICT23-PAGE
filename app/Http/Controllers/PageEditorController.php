<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePageDraftRequest;
use App\Models\Content;
use App\Models\Center;
use App\Models\Media;
use App\Models\Officer;
use App\Models\Song;
use App\Services\PageDrafts;
use App\Services\PageEditor;
use App\Support\MediaRules;
use Illuminate\Http\Request;

class PageEditorController extends Controller
{
    public function edit()
    {
        $draft = PageEditor::read('visual_draft');

        return response()->view('admin.page-editor', [
            'state' => $draft['page'] ?? PageEditor::current(),
            'revision' => $draft['revision'] ?? '',
            'groups' => config('cms'), 'editorConfig' => config('page_editor'),
            'uploadLimits' => MediaRules::limits(),
            'media' => Media::latest()->get()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'url' => $m->url, 'video' => $m->is_video]),
            'counts' => [
                'news' => Content::where('type', 'news')->published()->count(),
                'achievements' => Content::where('type', 'achievements')->published()->count(),
                'officers' => Officer::where('status', 'published')->count(),
                'songs' => Song::where('status', 'published')->whereNotNull('published_at')->count(),
                'centers' => Center::where('status', 'published')->whereNotNull('published_at')->count(),
            ],
            'recent' => Content::whereIn('type', ['news', 'achievements'])->latest('updated_at')->limit(6)->get(),
        ])->withHeaders(['Cache-Control' => 'no-store']);
    }

    public function preview(Request $request)
    {
        $request->attributes->set('visual_preview', PageEditor::read('visual_draft')['page'] ?? PageEditor::current());

        return response(app(PublicController::class)->home())->header('Cache-Control', 'no-store');
    }

    public function save(SavePageDraftRequest $request, PageDrafts $drafts)
    {
        $revision = $drafts->save($request->validated());

        return response()->json(['revision' => $revision, 'message' => 'Draft saved. Public page unchanged.']);
    }

    public function publish(Request $request, PageDrafts $drafts)
    {
        $drafts->publish($request->input('revision'));

        return response()->json(['message' => 'Published on this installation.']);
    }

    public function reset(PageDrafts $drafts)
    {
        $drafts->discard();

        return response()->json(['message' => 'Draft discarded. Current public page unchanged.']);
    }

    public function restore(PageDrafts $drafts)
    {
        $drafts->restore();

        return response()->json(['message' => 'Previous page settings restored.']);
    }
}
