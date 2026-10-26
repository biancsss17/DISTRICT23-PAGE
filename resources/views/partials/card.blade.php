<article class="content-card" data-reveal @if(\App\Services\PageEditor::preview()) data-editor-record="{{ $record->type }}:{{ $record->id }}" @endif>
<a class="card-image" href="{{ route('content.show',[$record->type,$record->slug]) }}">
@if($record->cover)<img src="{{ $record->cover->url }}" alt="{{ $record->cover->alt ?: $record->title }}" loading="lazy">
@else<div class="image-placeholder variant-{{ $record->id % 3 }}"><span>✦</span><small>{{ $record->category ?: 'UECFI' }}</small></div>@endif
<span class="card-arrow" aria-hidden="true">↗</span></a>
<div class="card-meta"><span>{{ $record->category }}</span><time>{{ ($record->event_date ?: $record->published_at)?->format('M d, Y') }}</time></div>
<h3><a href="{{ route('content.show',[$record->type,$record->slug]) }}">{{ $record->title }}</a></h3><p>{{ $record->excerpt }}</p>
<a class="text-link" href="{{ route('content.show',[$record->type,$record->slug]) }}">Discover the story →</a>
</article>
