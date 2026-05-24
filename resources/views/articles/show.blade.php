@extends('layouts.site')

@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))

@section('content')
    @php($locale = app()->getLocale())
    @php($coverUrl = $article->getFirstMediaUrl('primary', 'detail'))

    <article style="padding: 32px 0 80px">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
                ['href' => route('articles.index', [], false), 'label' => __('site.nav.articles')],
                ['label' => $article->title],
            ]"/>

            <div class="article-head">
                <div class="meta">
                    @if($article->category)
                        <span class="tag">{{ $article->category }}</span>
                    @endif
                    @if($date = $article->displayDate())
                        <span>{{ $date }}</span>
                    @endif
                    @if($article->read_time_minutes)
                        <span>{{ $article->read_time_minutes }} {{ __('site.articles.read_min') }}</span>
                    @endif
                </div>
                <h1 class="article-title">{{ $article->title }}</h1>
                @if($article->intro)
                    <p class="lead">{{ $article->intro }}</p>
                @endif
            </div>

            @if($coverUrl)
                <div class="article-cover">
                    <img src="{{ $coverUrl }}" alt="{{ $article->title }}">
                </div>
            @endif

            <div class="article-body">
                {!! \Illuminate\Support\Str::markdown($article->content ?? '') !!}
            </div>
        </div>

        {{-- products + related sections added in Task 9 --}}
    </article>
@endsection
