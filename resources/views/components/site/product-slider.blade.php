@props([
    'products',
    'eyebrow' => null,
    'title' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
])

@php
    $eyebrow ??= __('catalogue.public.product.related.eyebrow');
    $title ??= __('catalogue.public.product.related.title');
    $ctaLabel ??= __('catalogue.public.product.related.all_label');
    $ctaUrl ??= route('products.index');
@endphp

@if($products->isNotEmpty())
<section class="product-slider">
    <div class="container">
        <div class="head">
            <div class="eyebrow">{{ $eyebrow }}</div>
            <h2>{{ $title }}</h2>
            @if($ctaLabel && $ctaUrl)
                <a href="{{ $ctaUrl }}" class="lnk">{{ $ctaLabel }} →</a>
            @endif
        </div>
        <div class="track">
            @foreach($products as $product)
                <x-site.product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif
