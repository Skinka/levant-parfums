@props(['item'])

@php
    $locale = app()->getLocale();
    $tr = function (string $key) use ($item, $locale) {
        $value = $item[$key][$locale] ?? null;
        return filled($value) ? $value : ($item[$key]['uk'] ?? '');
    };
    $quote  = $tr('quote');
    $author = $item['author'] ?? '';
    $city   = $tr('city');
    $rating = (int) ($item['rating'] ?? 0);
@endphp

<article class="review">
    <div class="quote-mark" aria-hidden="true">“</div>
    @if($quote)<p class="text">{{ $quote }}</p>@endif
    <div class="meta">
        <span>
            {{ $author }}@if($city) · {{ $city }}@endif
        </span>
        @if($rating > 0)
            <span class="stars" aria-label="{{ $rating }}/5">
                @for($i = 1; $i <= 5; $i++)
                    {{ $i <= $rating ? '★' : '☆' }}
                @endfor
            </span>
        @endif
    </div>
</article>
