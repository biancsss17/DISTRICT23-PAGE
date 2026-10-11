@extends('layouts.admin')
@section('content')
<a href="{{ route('admin.content.index',$type) }}">← {{ ucfirst($type) }}</a><div class="title-row"><h1>{{ $record->exists?'Edit story':'Create story' }}</h1>@if($record->exists)<a href="{{ route('admin.content.preview',[$type,$record]) }}" target="_blank" rel="noopener">Preview saved content ↗</a>@endif</div>
<form class="editor-form" enctype="multipart/form-data" method="post" action="{{ $record->exists?route('admin.content.update',[$type,$record]):route('admin.content.store',$type) }}">@csrf @if($record->exists) @method('PUT') @endif
<div class="editor-grid"><section class="panel">
@include('partials.admin-field',['field'=>'title','value'=>$record->title,'required'=>true])
@include('partials.admin-field',['field'=>'slug','value'=>$record->slug,'required'=>true])
@include('partials.admin-field',['field'=>'excerpt','value'=>$record->excerpt,'kind'=>'textarea','rows'=>3])
@include('partials.admin-field',['field'=>'content','value'=>$record->content,'kind'=>'textarea','rows'=>16])
<p class="hint">Plain text with paragraph breaks. HTML and scripts are displayed as text.</p>
<h2>Content slideshow</h2><p class="hint">Each story supports up to 3 photos and 1 video total, counting the cover. New slideshow uploads replace the current gallery. Maximum 50 MB per file.</p><label>Slideshow photos and videos<input type="file" name="gallery_uploads[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple></label>@if($record->gallery->isNotEmpty())<div class="media-picker">@foreach($record->gallery as $image)<div class="media-picker-item">@if($image->is_video)<video src="{{ $image->url }}" muted controls></video>@else<img src="{{ $image->url }}" alt="{{ $image->alt ?: $record->title }}" loading="lazy">@endif<button type="button" class="text-danger" data-remove-media="{{ route('admin.media.destroy',$image) }}" data-csrf="{{ csrf_token() }}" data-media-kind="{{ $image->is_video ? 'video' : 'photo' }}">Delete {{ $image->is_video ? 'video' : 'photo' }}</button></div>@endforeach</div>@endif
</section><aside class="panel">
<label>Status<select name="status"><option value="draft" @selected(old('status',$record->status)==='draft')>Draft</option><option value="published" @selected(old('status',$record->status)==='published')>Published</option></select></label>
@include('partials.admin-field',['field'=>'published_at','value'=>$record->published_at?->format('Y-m-d\TH:i')??now()->format('Y-m-d\TH:i'),'kind'=>'datetime-local'])
@include('partials.admin-field',['field'=>'category','value'=>$record->category])
@include('partials.admin-field',['field'=>'sort_order','value'=>$record->sort_order??0,'kind'=>'number','required'=>true])
<label class="check"><input type="checkbox" name="featured" value="1" @checked(old('featured',$record->featured))> Feature this story</label>
<label>Cover photo or video<input type="file" name="cover_upload" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg"></label>@if($record->cover)<div class="current-cover-media">@if($record->cover->is_video)<video class="selected-image" src="{{ $record->cover->url }}" controls muted></video>@else<img class="selected-image" src="{{ $record->cover->url }}" alt="Current cover photo">@endif<button type="button" class="text-danger" data-remove-media="{{ route('admin.media.destroy',$record->cover) }}" data-csrf="{{ csrf_token() }}" data-media-kind="{{ $record->cover->is_video ? 'video' : 'photo' }}">Delete cover {{ $record->cover->is_video ? 'video' : 'photo' }}</button></div>@endif<p class="hint">Maximum 50 MB per file. Cover media counts toward the per-story total.</p>
@if($type==='achievements')@include('partials.admin-field',['field'=>'event_date','label'=>'Achievement date','value'=>$record->event_date?->format('Y-m-d'),'kind'=>'date'])@endif
<button class="primary" data-save>Save changes</button>
</aside></div></form>
@endsection
