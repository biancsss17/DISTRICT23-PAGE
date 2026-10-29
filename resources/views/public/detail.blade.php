@extends('layouts.public')
@section('title', $record->title . ' · UECFI')
@section('content')
    <div class="reading-progress" aria-hidden="true">
    </div>
    <article class="wrap article-page">
        @if ($preview ?? false)
            <p class="preview-banner">Private preview · {{ $record->status }} · Only administrators can view this URL.</p>
        @endif
        <a class="text-link" href="/{{ $type }}">← All {{ $type }}</a>
        <p class="eyebrow">{{ $record->category }} / {{ $record->published_at?->format('F d, Y') }}</p>
        <h1>{{ $record->title }}</h1>
        <p class="article-lead">{{ $record->excerpt }}</p>
        @if ($record->cover)<button class="article-cover media-open"
                type="button" data-gallery="{{ $record->cover->url }}"
                data-kind="{{ $record->cover->is_video ? 'video' : 'image' }}"
                data-alt="{{ $record->cover->alt ?: $record->title }}" data-media-group="content-{{ $record->id }}"
                aria-label="Open {{ $record->cover->is_video ? 'video' : 'photo' }} gallery">
                @if ($record->cover->is_video)
                    <video src="{{ $record->cover->url }}" muted playsinline preload="metadata"></video><span
                    class="media-play" aria-hidden="true">▶</span>@else<img src="{{ $record->cover->url }}"
                        alt="{{ $record->cover->alt ?: $record->title }}">
                @endif
                <span class="media-open-hint">
                    View media ↗</span>
            </button>@endif
        @if ($record->event_date)
            <p class="event-meta">{{ $record->event_date->format('F d, Y') }} @if ($record->location)
                    · {{ $record->location }}
                @endif
            </p>
        @endif
        <div class="prose">{{ $record->content }}</div>
        @if ($record->gallery->count())
            <h2>Photos &amp; videos</h2>
            <div class="gallery-grid">
                @foreach ($record->gallery as $image)
                    <button class="gallery-image media-open" type="button" data-gallery="{{ $image->url }}"
                        data-kind="{{ $image->is_video ? 'video' : 'image' }}"
                        data-alt="{{ $image->alt ?: $record->title }}" data-media-group="content-{{ $record->id }}">
                        @if ($image->is_video)
                            <video src="{{ $image->url }}#t=0.1" muted playsinline preload="metadata"></video><span
                            class="media-play" aria-hidden="true">▶</span>@else<img src="{{ $image->url }}"
                                alt="{{ $image->alt ?: $record->title }}" loading="lazy">
                        @endif
                        <span>
                            View {{ $image->is_video ? 'video' : 'photo' }} ↗</span>
                    </button>
                @endforeach
            </div>
        @endif
    </article>
    @if ($related->count())
        <section class="section wrap">
            <p class="eyebrow">Keep exploring</p>
            <h2>More from our community.</h2>
            <div class="content-grid">@each('partials.card', $related, 'record')</div>
        </section>
    @endif
@endsection
