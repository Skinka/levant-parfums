@props(['locale'])

@php
    $supported = LaravelLocalization::getSupportedLocales();
@endphp

<div class="lang" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="lang-btn" @click="open = !open" :aria-expanded="open">
        {{ strtoupper($locale === 'uk' ? 'UA' : $locale) }}
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div class="lang-menu" x-show="open" x-cloak x-transition>
        @foreach($supported as $code => $info)
            <a href="{{ LaravelLocalization::getLocalizedURL($code, null, [], true) }}"
               rel="alternate" hreflang="{{ $code }}"
               class="{{ $code === $locale ? 'active' : '' }}">
                <span>{{ $info['native'] ?? $info['name'] ?? strtoupper($code) }}</span>
                <span class="dot"></span>
            </a>
        @endforeach
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
