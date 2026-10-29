@extends('layouts.public')
@section('title', ucfirst($type) . ' · UECFI')
@section('content')
    <section class="page-heading wrap">
        <p class="eyebrow">UECFI / {{ $type }}</p>
        <h1>{{ $section->title }}</h1>
        <p>{{ $section->description }}</p>
    </section>
    <section class="wrap section">
        <div @class(['content-grid', 'timeline' => $type === 'achievements'])>@each('partials.card', $records, 'record', 'partials.empty')</div>
        <div class="pagination">{{ $records->links('partials.pagination') }}</div>
    </section>
@endsection
