@extends('layouts.public')
@section('title', 'Songs · UECFI')
@section('content')
    <section class="page-heading wrap">
        <p class="eyebrow">UECFI / SONGS</p>
        <h1>Song library</h1>
        <p>Lyrics and approved video references for worship and service preparation.</p>
    </section>
    <section class="wrap section songs-page">
        @forelse ($songs as $song)
            <article class="song-card" id="{{ $song->slug }}">
                <div class="song-card-heading">
                    <div>
                        @if ($song->category)<p class="eyebrow">{{ $song->category }}</p>@endif
                        <h2>{{ $song->title }}</h2>
                    </div>
                    <span class="song-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="song-card-body">
                    <div class="lyrics" aria-label="Lyrics for {{ $song->title }}">{!! nl2br(e($song->lyrics)) !!}</div>
                    @if ($song->video_embed_url)
                        <div class="song-video"><iframe src="{{ $song->video_embed_url }}" title="{{ $song->title }} video"
                                loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe></div>
                    @elseif ($song->video_url)
                        <div class="song-video"><video src="{{ $song->video_url }}" controls preload="metadata"></video></div>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state"><h2>Songs are being prepared.</h2><p>Published lyrics will appear here.</p></div>
        @endforelse
    </section>
@endsection
