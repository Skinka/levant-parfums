@extends('layouts.site')

@section('title', $page->seo_title ?: $page->title)
@if($page->seo_description)
    @section('description', $page->seo_description)
@endif

@section('content')
    <section class="tight">
        <div class="container">
            <article>
                <h1>{{ $page->title }}</h1>
                {!! Str::markdown($page->content ?? '') !!}
            </article>
        </div>
    </section>
@endsection
