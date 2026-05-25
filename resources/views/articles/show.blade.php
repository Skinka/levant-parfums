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
                    <img src="{{ $coverUrl }}" alt="{{ $article->title }}"
                         width="1920" height="1080" fetchpriority="high">
                </div>
            @endif

            <div class="article-body">
                {!! \Illuminate\Support\Str::markdown($article->content ?? '') !!}
            </div>
        </div>

        @if($products->isNotEmpty())
            <div class="in-article-products">
                <x-site.product-slider
                    :products="$products"
                    :eyebrow="__('site.articles.in_article_products')"
                    :title="$article->title"
                    cta-label=""
                    cta-url=""/>
            </div>
        @endif

        @if($related->isNotEmpty())
            <section class="related-articles">
                <div class="container">
                    <div class="eyebrow">{{ __('site.articles.eyebrow', ['year' => now()->year]) }}</div>
                    <h2 style="font-style: italic; margin-top: 12px">{{ __('site.articles.related_title') }}</h2>
                    <div class="articles-grid articles-grid--3">
                        @foreach($related as $relatedArticle)
                            @php($relatedCover = $relatedArticle->getFirstMediaUrl('primary', 'card'))
                            <a class="article-card"
                               href="{{ route('articles.show', $relatedArticle->getTranslation('slug', $locale)) }}">
                                <div class="cover">
                                    @if($relatedCover)
                                        <img src="{{ $relatedCover }}" alt="{{ $relatedArticle->title }}"
                                             loading="lazy" width="800" height="600">
                                    @endif
                                </div>
                                <div class="meta">
                                    @if($relatedArticle->category)
                                        <span class="tag">{{ $relatedArticle->category }}</span>
                                    @endif
                                    @if($d = $relatedArticle->displayDate())
                                        <span>{{ $d }}</span>
                                    @endif
                                </div>
                                <h3>{{ $relatedArticle->title }}</h3>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </article>
@endsection
