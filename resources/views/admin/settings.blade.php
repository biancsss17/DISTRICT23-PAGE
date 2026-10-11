@extends('layouts.admin')
@section('content')
<p class="eyebrow">Website editor</p><div class="title-row"><h1>{{ ucfirst($group) }} settings</h1></div>
<div class="settings-layout"><section class="panel"><form method="post" action="/admin/settings/{{ $group }}" id="settings-form" enctype="multipart/form-data">@csrf @method('PUT')
@foreach($fields as $key=>$field)
@if($field[1]==='media')@include('partials.media-select',['field'=>$key,'label'=>$field[0],'value'=>$site[$key]])
@else @include('partials.admin-field',['field'=>$key,'label'=>$field[0],'kind'=>in_array($field[1],['textarea','number','color','email','url'])?$field[1]:'text','value'=>$site[$key]])
@endif
@endforeach
@if($group==='home')
<fieldset class="hero-images-editor"><legend>Homepage cover images</legend>
<p class="hint">Select several images for the rotating cover. Lower order numbers show first. With no selection, the default cover is used.</p>
<div class="hero-image-options">
@forelse($media as $image)
<div class="hero-image-option">
<label class="check"><input type="checkbox" name="hero_media[]" value="{{ $image->id }}" @checked(in_array($image->id, old('hero_media', $selectedHeroIds)))>@if($image->is_video)<video src="{{ $image->url }}" muted controls preload="metadata"></video>@else<img src="{{ $image->url }}" alt="{{ $image->alt ?: $image->name }}" loading="lazy">@endif</label><button type="button" class="text-danger" data-remove-media="{{ route('admin.media.destroy', $image) }}" data-csrf="{{ csrf_token() }}" data-media-kind="{{ $image->is_video ? 'video' : 'photo' }}">Delete {{ $image->is_video ? 'video' : 'photo' }}</button>
<label>Order<input type="number" name="hero_order[{{ $image->id }}]" min="0" max="10000" value="{{ old('hero_order.'.$image->id, ($index = array_search($image->id, $selectedHeroIds)) !== false ? $index + 1 : 100) }}"></label>
</div>
@empty
<p class="hint">No uploaded images yet. Add files below.</p>
@endforelse
</div>
<label>Add new cover photos or videos (up to 10)<input type="file" name="hero_uploads[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple></label>
<p class="hint">Maximum 50 MB per photo or video. New uploads are added after selected slides and play automatically.</p>
</fieldset>
@endif
<div class="sticky-actions"><button class="primary" data-save>Save changes</button><small>Changes become public immediately after saving.</small></div></form></section><aside class="panel preview-panel"><h2>Website preview</h2><div class="desktop-preview-stage"><iframe title="Website preview in desktop layout" id="site-preview" src="/?editor-preview=1" sandbox="allow-same-origin allow-scripts"></iframe></div><button type="button" id="refresh-preview">Refresh saved preview ↻</button></aside></div>
@if($group==='home')<section class="panel"><h2>Homepage sections</h2><p>Control headings, descriptions, order and visibility. Lower order numbers appear first.</p><form method="post" action="/admin/sections">@csrf @method('PUT')@foreach($sections as $i=>$section)<fieldset><legend>{{ ucfirst($section->key) }}</legend><input type="hidden" name="sections[{{ $i }}][id]" value="{{ $section->id }}"><label>Heading<input name="sections[{{ $i }}][title]" value="{{ old('sections.'.$i.'.title',$section->title) }}" required></label><label>Description<textarea name="sections[{{ $i }}][description]" rows="2">{{ old('sections.'.$i.'.description',$section->description) }}</textarea></label><div class="actions"><label>Order<input type="number" min="0" name="sections[{{ $i }}][sort_order]" value="{{ old('sections.'.$i.'.sort_order',$section->sort_order) }}" required></label><label class="check"><input type="checkbox" name="sections[{{ $i }}][visible]" value="1" @checked($section->visible)> Visible</label></div></fieldset>@endforeach<button class="primary" data-save>Save sections</button></form></section>@endif
@endsection
