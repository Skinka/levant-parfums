@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };

    $ids = collect($data['items'] ?? [])->pluck('article_id')->filter()->take(3)->all();
    $articles = $ids
        ? \App\Models\Content\Article::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();

    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $ctaLabel = $t('cta_label');
    $ctaUrl = $data['cta_url'] ?? null;
@endphp

@if($articles->isNotEmpty())
    <section class="blog reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
        <div class="container">
            <div class="section-head">
                <div>
                    @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                    @if($title)<h2 style="margin-top: 12px;">{{ $title }}</h2>@endif
                </div>
                @if($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="lnk">{{ $ctaLabel }} →</a>
                @endif
            </div>

            <div class="grid">
                @foreach($ids as $id)
                    @if($article = $articles[$id] ?? null)
                        <x-site.article-card :article="$article" />
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
