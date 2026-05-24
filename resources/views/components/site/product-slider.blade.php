@props(['products'])

@if($products->isNotEmpty())
<section class="product-slider">
    <div class="container">
        <div class="head">
            <div class="eyebrow">{{ __('catalogue.public.product.related.eyebrow') }}</div>
            <h2>{{ __('catalogue.public.product.related.title') }}</h2>
            <a href="{{ route('products.index') }}" class="lnk">{{ __('catalogue.public.product.related.all_label') }} →</a>
        </div>
        <div class="track">
            @foreach($products as $product)
                <x-site.product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif
