@props(['product'])

@php
    $gallery = $product->getMedia('gallery');
    if ($gallery->isEmpty() && $product->getFirstMedia('primary')) {
        $gallery = collect([$product->getFirstMedia('primary')]);
    }
    $urls = $gallery->map(fn ($m) => $m->getUrl('detail'))->values()->all();
    $cardUrls = $gallery->map(fn ($m) => $m->getUrl('thumb'))->values()->all();
@endphp

<div class="gallery">
    @if(count($urls) > 1)
        <div class="thumbs">
            @foreach($cardUrls as $i => $thumb)
                <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-thumb-index="{{ $i }}">
                    <img src="{{ $thumb }}" alt="">
                </button>
            @endforeach
        </div>
    @endif

    <button
        type="button"
        class="main-img"
        data-lightbox-trigger
        data-lightbox-images='@json($urls)'
        data-lightbox-index="0"
        aria-label="{{ __('catalogue.public.product.gallery_open') }}"
    >
        @if(! empty($urls))
            <img src="{{ $urls[0] }}" alt="{{ $product->name }}" data-main-image>
        @else
            <span class="placeholder"></span>
        @endif
        <span class="zoom-hint">{{ __('catalogue.public.product.gallery_zoom') }}</span>
    </button>
</div>
