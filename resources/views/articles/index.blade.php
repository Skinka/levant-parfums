@extends('layouts.site')

@section('title', __('site.articles.meta_title'))
@section('description', __('site.articles.meta_description'))

@section('content')
    @php($locale = app()->getLocale())

    <section style="padding: 32px 0 120px">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
                ['label' => __('site.nav.articles')],
            ]"/>

            <div class="section-head" style="margin-top: 32px">
                <div>
                    <div class="eyebrow">{{ __('site.articles.eyebrow', ['year' => now()->year]) }}</div>
                    <h1 style="margin-top: 18px; font-style: italic">{{ __('site.articles.title') }}</h1>
                    <p class="lead" style="margin-top: 24px; max-width: 44ch">
                        {{ __('site.articles.subtitle') }}
                    </p>
                </div>
            </div>

            @if($articles->isEmpty())
                <p class="lead" style="margin-top: 80px">—</p>
            @else
                <div class="articles-grid reveal-stagger">
                    @foreach($articles as $article)
                        @php($coverUrl = $article->getFirstMediaUrl('primary', 'card'))
                        <a class="article-card"
                           href="{{ route('articles.show', $article->getTranslation('slug', $locale)) }}">
                            <div class="cover">
                                @if($coverUrl)
                                    <img src="{{ $coverUrl }}" alt="{{ $article->title }}"
                                         loading="lazy" width="1200" height="630">
                                @endif
                            </div>
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
                            <h3>{{ $article->title }}</h3>
                            @if($article->intro)
                                <p>{{ $article->intro }}</p>
                            @endif
                            <span class="lnk">{{ __('site.articles.read_more') }} →</span>
                        </a>
                    @endforeach
                </div>

                {{ $articles->onEachSide(1)->links('vendor.pagination.site') }}
            @endif
        </div>
    </section>
@endsection
