@extends('layouts.public')
@section('title', 'CENTRO map · UECFI')
@section('content')
    <section class="page-heading wrap">
        <p class="eyebrow">UECFI / CENTRO</p>
        <h1>Find a CENTRO</h1>
        <p>Select a CENTRO from the list to view its exact Google Maps location and details.</p>
    </section>

    <section class="wrap section centro-directory" aria-label="CENTRO directory">
        @forelse ($centers as $center)
            @if ($loop->first)
                <div class="centro-list" role="list" aria-label="Available CENTRO locations">
                    <div class="centro-list-intro">
                        <span class="centro-list-kicker">DISTRICT 23 DIRECTORY</span>
                        <h2>Choose your CENTRO</h2>
                        <p>Explore each location, then open its exact map.</p>
                        <span class="centro-count">{{ $centers->count() }} locations</span>
                    </div>
            @endif
                    <button class="centro-list-item" type="button"
                        data-centro-target="{{ $center->slug }}" aria-expanded="false">
                        <span class="centro-list-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="centro-list-copy">
                            <strong>{{ $center->name }}</strong>
                            <small>{{ trim($center->address) ?: ($center->latitude !== null && $center->longitude !== null ? number_format($center->latitude, 5).', '.number_format($center->longitude, 5) : 'Location details pending') }}</small>
                        </span>
                        <span class="centro-list-arrow" aria-hidden="true">↗</span>
                    </button>
            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="empty-state"><h2>CENTRO locations are being prepared.</h2><p>Published locations will appear here.</p></div>
        @endforelse

        @if ($centers->isNotEmpty())
            <div class="centro-detail-area">
                <p class="centro-detail-prompt" data-centro-prompt>Choose a CENTRO above to view its map.</p>
                @foreach ($centers as $center)
                    <article class="center-card centro-detail" id="centro-panel-{{ $center->slug }}" data-centro-panel="{{ $center->slug }}" tabindex="-1" hidden>
                        <div class="center-card-heading">
                            <div><p class="eyebrow">CENTRO location</p><h2>{{ $center->name }}</h2></div>
                            @if ($center->latitude !== null && $center->longitude !== null)<span class="center-coordinates">{{ number_format($center->latitude, 5) }}, {{ number_format($center->longitude, 5) }}</span>@endif
                        </div>
                        @if ($center->description)<p class="muted">{{ $center->description }}</p>@endif
                        @if (trim($center->address))<p class="center-address">{{ $center->address }}</p>@endif
                        @if ($center->map_embed_url)
                            <div class="center-map"><iframe src="https://www.google.com/maps?q={{ $center->latitude }},{{ $center->longitude }}&amp;z=17&amp;output=embed" title="Google Map of {{ $center->name }}" loading="lazy"></iframe></div>
                            <a class="text-link" href="{{ $center->map_url ?: 'https://www.openstreetmap.org/?mlat='.$center->latitude.'&mlon='.$center->longitude.'#map=17/'.$center->latitude.'/'.$center->longitude }}" target="_blank" rel="noopener">Open exact Google Maps location ↗</a>
                        @elseif ($center->map_url)
                            <a class="button outline" href="{{ $center->map_url }}" target="_blank" rel="noopener">Open map location ↗</a>
                        @else
                            <p class="map-pending">The exact coordinates for this CENTRO will be added by an administrator.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($centers->isNotEmpty())
        <script>
            (() => {
                const buttons = [...document.querySelectorAll('[data-centro-target]')];
                const panels = [...document.querySelectorAll('[data-centro-panel]')];
                const prompt = document.querySelector('[data-centro-prompt]');
                const openCentro = (slug, updateHash = true) => {
                    const button = buttons.find((item) => item.dataset.centroTarget === slug);
                    const panel = panels.find((item) => item.dataset.centroPanel === slug);
                    if (!button || !panel) return;
                    buttons.forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-selected', active);
                        item.setAttribute('aria-expanded', active ? 'true' : 'false');
                    });
                    panels.forEach((item) => { item.hidden = item !== panel; });
                    if (prompt) prompt.hidden = true;
                    if (updateHash) history.replaceState(null, '', `#${slug}`);
                    panel.focus({ preventScroll: true });
                };
                buttons.forEach((button) => button.addEventListener('click', () => openCentro(button.dataset.centroTarget)));
                const initial = window.location.hash.slice(1);
                if (initial) openCentro(initial, false);
            })();
        </script>
    @endif
@endsection
