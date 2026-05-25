@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $ctaLabel = $t('cta_label');
    $ctaUrl = $data['cta_url'] ?? null;
    $items = $data['items'] ?? [];
@endphp

<section class="testimonials reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
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

        @if(! empty($items))
            <div class="track">
                @foreach($items as $item)
                    <x-site.review-card :item="$item" />
                @endforeach
            </div>
        @endif
    </div>
</section>
