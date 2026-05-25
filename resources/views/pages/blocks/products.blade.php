@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };

    $ids = collect($data['items'] ?? [])->pluck('product_id')->filter()->all();
    $products = $ids
        ? \App\Models\Catalogue\Product::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();

    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $ctaLabel = $t('cta_label');
    $ctaUrl = $data['cta_url'] ?? null;
@endphp

@if($products->isNotEmpty())
    <section class="product-slider reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
        <div class="container">
            <div class="head">
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h2>{{ $title }}</h2>@endif
                @if($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="lnk">{{ $ctaLabel }} →</a>
                @endif
            </div>
            <div class="track">
                @foreach($ids as $id)
                    @if($product = $products[$id] ?? null)
                        <x-site.product-card :product="$product" />
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
