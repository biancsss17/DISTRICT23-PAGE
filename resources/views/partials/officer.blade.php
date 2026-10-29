<article class="officer-card" data-reveal>
    <button class="officer-open" data-dialog="officer-{{ $officer->id }}"
        aria-label="Read profile of {{ $officer->name }}">
        <div class="officer-photo">
            @if ($officer->photo)
                <img src="{{ $officer->photo->url }}" alt="{{ $officer->photo->alt ?: $officer->name }}"
                loading="lazy">@else<span
                    class="initials">{{ collect(explode(' ', $officer->name))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->implode('') }}</span>
            @endif
            <span class="card-arrow">
                ↗</span>
        </div>
        <p class="eyebrow">{{ $officer->position }}</p>
        <h3>{{ $officer->name }}</h3><small>{{ $officer->term }}</small>
    </button>
</article>
<dialog id="officer-{{ $officer->id }}" class="profile-dialog" aria-labelledby="officer-title-{{ $officer->id }}">
    <button data-close class="dialog-close" aria-label="Close profile">×</button>
    <p class="eyebrow">{{ $officer->position }}</p>
    <h2 id="officer-title-{{ $officer->id }}">{{ $officer->name }}</h2>
    <p class="prose">{{ $officer->biography }}</p>
    <p>{{ $officer->term }}</p>
    @if ($officer->gallery->isNotEmpty())
        <div class="gallery-grid">
            @foreach ($officer->gallery as $media)
                <button class="gallery-image media-open" type="button" data-gallery="{{ $media->url }}"
                    data-kind="{{ $media->is_video ? 'video' : 'image' }}"
                    data-alt="{{ $media->alt ?: $officer->name }}" data-media-group="officer-{{ $officer->id }}">
                    @if ($media->is_video)
                        <video src="{{ $media->url }}#t=0.1" muted playsinline preload="metadata"></video><span
                        class="media-play" aria-hidden="true">▶</span>@else<img src="{{ $media->url }}"
                            alt="{{ $media->alt ?: $officer->name }}" loading="lazy">
                    @endif
                    <span>
                        View {{ $media->is_video ? 'video' : 'photo' }} ↗</span>
                </button>
            @endforeach
        </div>
        @endif @if ($officer->email)
            <a href="mailto:{{ $officer->email }}">{{ $officer->email }}</a>
            @endif @if ($officer->social_url)
                <a href="{{ $officer->social_url }}" rel="noopener noreferrer">Social profile ↗</a>
            @endif
</dialog>
