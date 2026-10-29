@extends('layouts.public')
@section('bodyclass', 'home-page')
@section('content')
    <div id="splash" hidden role="dialog" aria-label="UECFI introduction" aria-modal="true">
        <div class="splash-glow"></div>
        <img class="splash-logo" src="{{ \App\Services\Site::data()['logo_url'] }}" alt="Official UECFI emblem">
        <p class="splash-welcome">UECFI DISTRICT 23 FYS OFFICIAL WEBSITE</p>
        <div class="star-flash" aria-hidden="true">★</div><button id="skip-intro">Skip intro →</button>
    </div>
    @php($s = \App\Services\Site::data())
    <section class="hero" id="home">
        <div class="hero-ribbons" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
        @php($heroSlides = $s['hero_slides'] ?: [['url' => asset('images/landscape.jpg'), 'video' => false, 'alt' => 'UECFI landscape']])
        <div class="hero-backdrops" data-hero-slider aria-hidden="true">
            @foreach ($heroSlides as $index => $slide)
                @if ($slide['video'])
                    <video class="hero-background hero-video @if ($index === 0) is-active @endif"
                        src="{{ $slide['url'] }}" muted playsinline preload="metadata"
                    @if ($index === 0) autoplay @endif></video>@else<img
                        class="hero-background @if ($index === 0) is-active @endif" src="{{ $slide['url'] }}"
                        alt="{{ $slide['alt'] }}"
                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                @endif
            @endforeach
        </div>
        @if (count($heroSlides) > 1)
            <div class="hero-slider-controls" role="group" aria-label="Homepage cover images"><button type="button"
                    data-slide-prev aria-label="Previous cover image">←</button><span data-slide-status aria-live="off">1 /
                    {{ count($heroSlides) }}</span><button type="button" data-slide-next
                    aria-label="Next cover image">→</button><button type="button" data-slide-pause
                    aria-label="Pause cover images" aria-pressed="false">Ⅱ</button></div>
        @endif
        <div class="hero-shade"></div>
        <div class="hero-grid wrap">
            <div class="hero-copy">
                <p class="eyebrow hero-reveal"><span class="tiny-star">✦</span> {{ $s['hero_kicker'] }}</p>
                <h1>
                    @foreach (explode("\n", $s['hero_heading']) as $line)
                        <span class="headline-line">{{ $line }}</span>
                    @endforeach
                </h1>
                <p class="hero-description hero-reveal">{{ $s['hero_description'] }}</p>
                <div class="button-row hero-reveal"><a class="button gold"
                        href="{{ $s['primary_url'] }}">{{ $s['primary_text'] }} <span>↗</span></a><a class="button outline"
                        href="{{ $s['secondary_url'] }}">{{ $s['secondary_text'] }} <span>→</span></a></div>
            </div>
            <div class="hero-mark hero-reveal">
                <div class="emblem-orbit"></div><img src="{{ $s['logo_url'] }}" alt="UECFI official emblem">
                <p>{{ $s['hero_motto'] }}</p>
            </div>
        </div>
    </section>
    @foreach ($sections as $section)
        @if ($section->key === 'heritage')
            <section class="heritage wrap section" id="heritage">
                <div class="heritage-year" aria-label="{{ $s['heritage_year'] }}">
                    @foreach (str_split($s['heritage_year']) as $digit)
                        <span data-reveal>{{ $digit }}</span>
                    @endforeach
                </div>
                <div class="heritage-copy" data-reveal>
                    <p class="eyebrow">{{ $s['heritage_kicker'] }}</p>
                    <h2>{{ $section->title }}</h2>
                    <div class="gold-line"></div>
                    <p class="muted">{{ $section->description }}</p>
                </div>
            </section>
        @elseif($section->key === 'statistics')
            <section class="stats-section" id="statistics">
                <div class="wrap stats-heading">
                    <h2>{{ $section->title }}</h2>
                    <p class="muted">{{ $section->description }}</p>
                </div>
                <div class="wrap stats">
                    @foreach (range(1, 4) as $i)
                        <div data-reveal><strong><span
                                    data-count="{{ $s['stat_' . $i . '_value'] }}">{{ $s['stat_' . $i . '_value'] }}</span>+</strong>
                            <p>{{ $s['stat_' . $i . '_label'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="stats-note">{{ $s['stats_note'] }}</p>
            </section>
        @elseif($section->key === 'officers')
            <section class="section wrap" id="officers">
                <div class="section-heading" data-reveal>
                    <div>
                        <p class="eyebrow">Our people</p>
                        <h2>{{ $section->title }}</h2>
                        <p class="muted">{{ $section->description }}</p>
                    </div><a class="text-link" href="/officers">{{ $s['officers_link'] }} ↗</a>
                </div>
                <div class="officer-grid">@each('partials.officer', $officers, 'officer', 'partials.empty')</div>
            </section>
        @else
            <section id="{{ $section->key }}" @class([
                'section',
                'light-section' => $section->key === 'news',
                'achievement-section' => $section->key === 'achievements',
            ])>
                <div class="wrap">
                    <div class="section-heading" data-reveal>
                        <div>
                            <p class="eyebrow">{{ $section->key }}</p>
                            <h2>{{ $section->title }}</h2>
                            <p class="muted">{{ $section->description }}</p>
                        </div><a href="/{{ $section->key }}" class="text-link">{{ $s[$section->key . '_link'] }} ↗</a>
                    </div>
                    <div class="content-grid">@each('partials.card', $featured[$section->key] ?? collect(), 'record', 'partials.empty')</div>
                </div>
            </section>
        @endif
    @endforeach
@endsection
