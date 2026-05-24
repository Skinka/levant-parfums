@props(['product'])

@php
    $firstOccasion = $product->relationLoaded('occasions') ? $product->occasions->first() : null;
    $tags = $product->relationLoaded('tags') ? $product->tags : collect();
    $isNew = $tags->contains('slug', 'new');
    $isBest = $tags->contains('slug', 'bestseller');
@endphp

<div class="info">
    @if($product->series)
        <div class="series">— {{ $product->series->name }}</div>
    @endif

    <h1 class="display-italic">{{ $product->name }}</h1>

    @if($product->tagline)
        <div class="subtitle">{{ $product->tagline }}</div>
    @endif

    @if($product->character || $firstOccasion)
        <div class="character-line">
            @if($product->character)
                <span class="accent">{{ $product->character }}</span>
            @endif
            @if($product->character && $firstOccasion) · @endif
            @if($firstOccasion)
                <span>{{ $firstOccasion->name }}</span>
            @endif
        </div>
    @endif

    @if($isNew || $isBest)
        <div class="badges">
            @if($isNew)<span class="badge badge-new">{{ __('catalogue.public.product.badges.new') }}</span>@endif
            @if($isBest)<span class="badge badge-best">{{ __('catalogue.public.product.badges.best') }}</span>@endif
        </div>
    @endif

    @php($price = $product->displayPrice())
    <div class="price-row">
        <div class="price">{{ number_format((float) $price['amount'], 0, ',', ' ') }} {{ $price['currency'] }}</div>
        <div class="vol">{{ $product->volume_ml }} ml · eau de parfum</div>
    </div>

    @if($product->description)
        <p class="desc">{{ $product->description }}</p>
    @endif

    @if($product->why)
        <div class="why-block">
            <div class="l">{{ __('catalogue.public.product.why_label') }}</div>
            <p>{{ $product->why }}</p>
        </div>
    @endif

    <div class="specs">
        @if($product->sku)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.sku') }}</span><span class="v">{{ $product->sku }}</span></div>
        @endif
        <div class="row"><span class="l">{{ __('catalogue.public.product.specs.volume') }}</span><span class="v">{{ $product->volume_ml }} ml</span></div>
        @if($product->perfumeFamily)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.family') }}</span><span class="v">{{ $product->perfumeFamily->name }}</span></div>
        @endif
        @if($product->concentration)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.concentration') }}</span><span class="v">{{ $product->concentration->name }}</span></div>
        @endif
        <div class="row"><span class="l">{{ __('catalogue.public.product.specs.composed') }}</span><span class="v">{{ __('catalogue.public.product.specs.composed_value') }}</span></div>
        @if($product->series)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.series') }}</span><span class="v">{{ $product->series->name }}</span></div>
        @endif
    </div>

    <div class="cta-row">
        @if($product->in_stock)
            <a href="#order" class="btn">{{ __('catalogue.public.product.order_cta') }}</a>
        @else
            <a href="#order" class="btn btn-secondary">{{ __('catalogue.public.product.preorder_cta') }}</a>
        @endif
    </div>
</div>
