@props(['product'])

<div class="character">
    @if($product->sillage_score)
        @php($s = (int) $product->sillage_score)
        <div class="bar-row">
            <div class="top">
                <span class="l">{{ __('catalogue.public.product.character.sillage_label') }}</span>
                <span class="v">{{ __("catalogue.public.product.character.sillage_words.{$s}") }}</span>
            </div>
            <div class="bar"><div class="fill" style="width: {{ ($s / 5) * 100 }}%"></div></div>
            <div class="ticks">
                @for($i = 1; $i <= 5; $i++)<span>{{ $i }}</span>@endfor
            </div>
        </div>
    @endif

    @if($product->longevity_hours)
        @php($h = (int) $product->longevity_hours)
        <div class="bar-row">
            <div class="top">
                <span class="l">{{ __('catalogue.public.product.character.longevity_label') }}</span>
                <span class="v">{{ $h }}+ {{ __('catalogue.public.product.character.longevity_word_h') }}</span>
            </div>
            <div class="bar"><div class="fill" style="width: {{ ($h / 12) * 100 }}%"></div></div>
            <div class="ticks">
                <span>2h</span><span>4h</span><span>6h</span><span>8h</span><span>10h</span><span>12h</span>
            </div>
        </div>
    @endif
</div>
