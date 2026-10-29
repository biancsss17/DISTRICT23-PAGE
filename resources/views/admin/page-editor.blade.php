<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit website · D23</title>
    <link rel="stylesheet"
        href="{{ asset('css/page-editor.css') }}?v={{ filemtime(public_path('css/page-editor.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('css/admin-guide.css') }}?v={{ filemtime(public_path('css/admin-guide.css')) }}">
    <script src="{{ asset('js/admin-guide.js') }}?v={{ filemtime(public_path('js/admin-guide.js')) }}" defer></script>
    <script id="editor-data" type="application/json">{!! json_encode(['state'=>$state,'revision'=>$revision,'media'=>$media,'config'=>$editorConfig,'uploadLimits'=>$uploadLimits], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
    <script type="module" src="{{ asset('js/page-editor.js') }}?v={{ filemtime(public_path('js/page-editor.js')) }}" defer>
    </script>
    <script type="module" src="{{ asset('js/editor-records.js') }}?v={{ filemtime(public_path('js/editor-records.js')) }}"
        defer></script>
</head>

<body>
    <header class="editor-bar"><a class="editor-brand" href="/admin"><img src="{{ asset('images/uecfi-logo.png') }}"
                alt="UECFI"><span>D23 <small>VISUAL EDITOR</small></span></a><span class="draft-badge">PRIVATE
            DRAFT</span>
        <div class="viewport-switch" aria-label="Preview size"><button data-width="1440"
                aria-pressed="true">Desktop</button><button data-width="768" aria-pressed="false">Tablet</button><button
                data-width="390" aria-pressed="false">Phone</button></div>
        @include('partials.admin-guide', ['placement' => 'admin-guide-editor'])<button id="toggle-panel" aria-expanded="true">Edit panel</button><button
            id="save-draft">Save draft</button><button id="publish" class="primary">Publish changes ↗</button>
    </header>
    <div class="editor-workspace">
        <aside id="editor-panel">
            <div class="panel-heading">
                <p class="eyebrow">MAKE IT YOURS</p>
                <h1>Your website.<br>Your editor.</h1>
                <p>Click text on the page to edit it here. Save a draft to preview section and media changes.</p>
            </div>
            <div class="editor-tabs" role="tablist" aria-label="Editor controls"><button role="tab"
                    data-tab="content" aria-selected="true">Content</button><button role="tab" data-tab="design"
                    aria-selected="false">Design</button><button role="tab" data-tab="media"
                    aria-selected="false">Media</button><button role="tab" data-tab="sections"
                    aria-selected="false">Sections</button><button role="tab" data-tab="records"
                    aria-selected="false">Records</button></div>
            @include('admin.editor.content')
            @include('admin.editor.design')
            @include('admin.editor.media')
            @include('admin.editor.sections')
            @include('admin.editor.records')
            <details class="versions">
                <summary>Versions & administration</summary>
                <p>One shared draft for this site. Publish saves the previous page settings, section configuration and
                    cover selection. Record edits and deleted media are not restored.</p><button id="restore">Restore
                    previous publication</button><button id="discard" class="danger">Discard draft</button>
                <form method="post" action="/admin/logout">@csrf<button>Log out</button></form>
            </details>
        </aside>
        <main class="canvas-area">
            <div class="canvas-caption"><span>LIVE PAGE PREVIEW</span><span id="preview-size">1440 px · Desktop</span>
            </div>
            <div id="preview-stage"><iframe id="page-preview" title="Editable website preview"
                    src="/admin/editor/preview?editor-preview=1" sandbox="allow-same-origin allow-scripts"></iframe>
            </div>
            <div class="canvas-tip">Click to select · Edit in the panel · Save draft to refresh the complete preview
            </div>
        </main>
    </div>
    <div id="editor-status" role="status" aria-live="polite">Ready. Your public website is unchanged.</div>
    @include('admin.editor.record-dialog')
</body>

</html>
