@props(['article'])

@php
    /** @var \App\Models\Content\Article $article */
    $locale = app()->getLocale();
    $title = $article->getTranslation('title', $locale) ?: ($article->getTranslation('title', 'uk') ?? '');
    $intro = $article->getTranslation('intro', $locale) ?: ($article->getTranslation('intro', 'uk') ?? '');
    $imgUrl = $article->getFirstMediaUrl('primary', 'card') ?: null;
    $date = $article->published_at?->format('d M Y');

    $contentHtml = $article->getTranslation('content', $locale) ?: ($article->getTranslation('content', 'uk') ?? '');
    $words = preg_match_all('/[\p{L}\p{N}\']+/u', strip_tags($contentHtml));
    $readMin = max(1, (int) ceil(($words ?: 0) / 200));

    $url = \Illuminate\Support\Facades\Route::has('articles.show')
        ? route('articles.show', ['slug' => $article->getTranslation('slug', $locale) ?: $article->getTranslation('slug', 'uk')])
        : '#';
@endphp

<a href="{{ $url }}" class="article-card">
    <div class="cover">
        @if($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $title }}" loading="lazy" width="600" height="450">
        @else
            <div class="placeholder" aria-hidden="true">L</div>
        @endif
    </div>
    <div class="meta">
        @if($date)<span>{{ $date }}</span>@endif
        <span>{{ $readMin }} {{ __('site.articles.read_min') }}</span>
    </div>
    @if($title)<h3>{{ $title }}</h3>@endif
    @if($intro)<p>{{ \Illuminate\Support\Str::limit($intro, 140) }}</p>@endif
</a>
