@extends('layouts.site')

@section('title', $page->seo_title ?: $page->title)
@if($page->seo_description)
    @section('description', $page->seo_description)
@endif

@section('content')
    @php($coverUrl = $page->getFirstMediaUrl('primary', 'detail'))

    <article>
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
                ['label' => $page->title],
            ]"/>

            <div class="article-head">
                <h1 class="article-title">{{ $page->title }}</h1>
                @if($page->intro)
                    <p class="lead">{{ $page->intro }}</p>
                @endif
            </div>

            @if($coverUrl)
                <div class="article-cover">
                    <img src="{{ $coverUrl }}" alt="{{ $page->title }}"
                         width="1920" height="1080" fetchpriority="high">
                </div>
            @endif

            <div class="article-body">
                {!! \Illuminate\Support\Str::markdown($page->content ?? '') !!}
            </div>
        </div>
    </article>
@endsection
