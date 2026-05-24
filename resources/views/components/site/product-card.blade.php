@props(['product'])

@php
    /** @var \App\Models\Catalogue\Product $product */
    $locale       = app()->getLocale();
    $price        = $product->displayPrice($locale);
    $imageUrl     = $product->getFirstMediaUrl('primary', 'card') ?: null;
    $hasTagNew    = $product->tags->contains('slug', 'new');
    $hasTagBest   = $product->tags->contains('slug', 'bestseller');
    $seriesLabel  = $product->series?->name;
    $familyLabel  = $product->perfumeFamily?->name;
    $title        = $product->name;
    $subtitle     = $product->tagline;
    $priceLabel   = number_format((float) $price['amount'], 0, ',', "\u{00A0}") . ' ' . $price['currency'];
@endphp

<a href="{{ route('products.show', $product->slug) }}" class="card">
    <div class="img">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="lazy" width="600" height="800">
        @else
            <div class="placeholder" aria-hidden="true">L</div>
        @endif
        <div class="badges">
            @if($hasTagNew)
                <x-site.badge variant="gold">{{ __('catalogue.public.badge_new') }}</x-site.badge>
            @endif
            @if($hasTagBest)
                <x-site.badge>{{ __('catalogue.public.badge_best') }}</x-site.badge>
            @endif
        </div>
    </div>
    <div class="body">
        @if($seriesLabel)
            <span class="series">{{ $seriesLabel }}</span>
        @endif
        <span class="title">{{ $title }}</span>
        @if($subtitle)
            <span class="subtitle">{{ $subtitle }}</span>
        @endif
        @if($familyLabel)
            <div class="fam-row">
                <span class="dot"></span>
                <span>{{ $familyLabel }} · eau de parfum</span>
            </div>
        @endif
        <div class="meta">
            <span class="price">{{ $priceLabel }}</span>
            <span class="vol">{{ $product->volume_ml }} ml</span>
        </div>
    </div>
</a>
