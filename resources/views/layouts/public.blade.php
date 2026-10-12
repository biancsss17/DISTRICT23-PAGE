<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script>try{const params=new URLSearchParams(location.search),seen=sessionStorage.getItem('uecfi-intro')==='1',reduceMotion=matchMedia('(prefers-reduced-motion: reduce)').matches;if(location.pathname==='/'&&(!seen||params.get('show-intro')==='1')&&!reduceMotion&&!params.has('editor-preview')){document.documentElement.classList.add('intro-pending');window.setTimeout(()=>document.documentElement.classList.remove('intro-pending'),8000)}}catch{}</script>
<style>@keyframes splash-fallback-reveal{from{opacity:0;transform:translateY(14px) scale(.96);filter:blur(12px)}to{opacity:1;transform:none;filter:none}}html.intro-pending,html.intro-pending body{background:#071936!important}html.intro-pending body{visibility:hidden!important}html.intro-pending #splash{display:flex!important;visibility:visible!important;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;flex-direction:column;overflow:hidden;background:#071936}html.intro-pending:not(.intro-started) #splash .splash-glow{animation:splash-fallback-reveal .7s ease-out .2s both}html.intro-pending:not(.intro-started) #splash .splash-logo{animation:splash-fallback-reveal .8s ease-out .5s both}html.intro-pending:not(.intro-started) #splash .splash-welcome{animation:splash-fallback-reveal .5s ease-out .6s both}html.intro-pending #splash .splash-logo{transform:scale(.82);filter:blur(12px)}html.intro-pending #splash .splash-welcome{transform:translateY(14px)}</style>
<script src="{{ asset('js/theme.js') }}"></script>
<title>@yield('title', $site['header_title'].' · '.$site['header_subtitle'])</title>
<meta name="description" content="{{ $site['hero_description'] }}">
<link rel="icon" href="{{ $site['favicon_url'] }}">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
<link rel="stylesheet" href="{{ asset('css/hero-background.css') }}?v={{ filemtime(public_path('css/hero-background.css')) }}">
<style>:root{--navy:{{ $site['primary_color'] }};--gold:{{ $site['secondary_color'] }};--purple:{{ $site['accent_color'] }}}</style>
<script src="{{ asset('js/vendor/gsap.min.js') }}" defer></script>
<script src="{{ asset('js/vendor/ScrollTrigger.min.js') }}" defer></script>
<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
<script src="{{ asset('js/background-interaction.js') }}" defer></script>
</head>
<body class="@yield('bodyclass')" id="top">
<div class="site-ribbons hero-ribbons" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
<a class="skip-link" href="#main">Skip to content</a>
<button class="theme-toggle" type="button" data-theme-toggle aria-label="Dark mode" aria-pressed="false" hidden><span aria-hidden="true" data-theme-icon>☾</span> <span data-theme-label>Dark mode</span></button>
<header class="navbar" id="navbar">
<a class="brand" href="/"><img src="{{ $site['logo_url'] }}" alt="Official UECFI logo" width="52" height="52"><span><b>{{ $site['header_title'] }}</b><small>{{ $site['header_subtitle'] }}</small></span></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Open navigation">☰</button>
<nav id="main-nav" aria-label="Main navigation">
@foreach(['home'=>'/','news'=>'/news','achievements'=>'/achievements','officers'=>'/officers','admin'=>'/admin/login'] as $key=>$url)
<a href="{{ $url }}" @class(['active'=>$url==='/'?request()->is('/'):str_starts_with(request()->getPathInfo(),$url),'admin-link'=>$key==='admin'])>{{ $site['nav_'.$key] }} @if($key==='admin') <span>↗</span> @endif</a>
@endforeach
</nav></header>
<main id="main">@yield('content')</main>
<footer class="site-footer"><div class="wrap footer-top"><div><p class="eyebrow">{{ $site['acronym'] }} / {{ $site['district'] }}</p><h2>{{ $site['footer_heading'] }}</h2><p>{{ $site['footer_text'] }}</p></div><img src="{{ $site['logo_url'] }}" alt="UECFI" width="150" height="150"></div><div class="wrap footer-bottom"><div>
@foreach(['home'=>'/','news'=>'/news','achievements'=>'/achievements','officers'=>'/officers'] as $key=>$url)<a href="{{ $url }}">{{ $site['nav_'.$key] }}</a>@endforeach
</div><div><p>{{ $site['organization_name'] }}</p>@if($site['email'])<a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>@endif @if($site['phone'])<span>{{ $site['phone'] }}</span>@endif <p>{{ $site['address'] }}</p>@foreach(['facebook','instagram'] as $social)@if($site[$social])<a href="{{ $site[$social] }}" rel="noopener noreferrer">{{ ucfirst($social) }}</a>@endif @endforeach</div><a href="#top">Back to top ↑</a></div><div class="wrap copyright">{{ $site['copyright'] }}</div></footer>
<div class="page-wipe" aria-hidden="true"></div>
<dialog id="lightbox" aria-label="Photo and video gallery"><button class="dialog-close" data-close aria-label="Close media viewer">×</button><img data-lightbox-image alt=""><video data-lightbox-video controls playsinline></video><p class="lightbox-caption"></p><div class="gallery-controls"><button data-prev aria-label="Previous media">←</button><span class="gallery-counter"></span><button data-next aria-label="Next media">→</button></div></dialog>
</body></html>
