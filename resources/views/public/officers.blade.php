@extends('layouts.public')
@section('title', 'Officers · UECFI')
@section('content')
    <section class="page-heading wrap">
        <p class="eyebrow">Our leadership</p>
        <h1>{{ $section->title }}</h1>
        <p>{{ $section->description }}</p>
    </section>
    <section class="section wrap officer-directory">
        <div class="officer-grid">
            @forelse($officers as $officer)
                @include('partials.officer', ['officer' => $officer])@empty @include('partials.empty')
            @endforelse
        </div>
    </section>
@endsection
