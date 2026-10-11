<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>@yield('title','Admin') · UECFI</title><link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}"><script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script></head><body>
<aside class="sidebar"><a class="admin-brand" href="/admin"><img src="{{ $site['logo_url'] }}" width="42" height="42" alt="UECFI"><span>UECFI FYS <small>DISTRICT 23</small></span></a><button class="admin-menu" aria-expanded="false" aria-controls="admin-navigation">Menu ☰</button><nav id="admin-navigation">
@foreach(['Dashboard'=>'/admin','Home'=>'/admin/settings/home','News'=>'/admin/content/news','Achievements'=>'/admin/content/achievements','Officers'=>'/admin/officers','Navigation'=>'/admin/settings/navigation','Footer'=>'/admin/settings/footer','Website Settings'=>'/admin/settings/organization'] as $label=>$url)<a href="{{ $url }}" @class(['selected'=>request()->getPathInfo()===$url])>{{ $label }}</a>@endforeach
<form action="/admin/logout" method="post">@csrf<button>Log out ↗</button></form></nav></aside>
<main class="admin-main"><header class="admin-top"><span>{{ auth()->user()->name }}</span></header>
@if(session('success'))<div class="toast" role="status">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="errors" role="alert"><b>Please check the following:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main>
<dialog id="delete-confirm"><h2>Delete this item?</h2><p>This action cannot be easily undone.</p><div class="actions"><button type="button" data-cancel>Cancel</button><button type="button" class="danger" data-confirm>Delete</button></div></dialog>
</body></html>
