@props(['locale'])

@php
    $supported = LaravelLocalization::getSupportedLocales();
    $alternateSlugs = view()->shared('alternateSlugs', []);
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
            @php
                // Always emit URLs with an explicit locale prefix. The localization
                // middleware will canonicalize (and update the locale session) on the
                // way in — emitting a prefix-less URL for the default locale would
                // leave the session locale stale and trigger a wrong-slug redirect.
                if (! empty($alternateSlugs) && ! empty($alternateSlugs[$code])) {
                    $href = url('/'.$code.'/'.$alternateSlugs[$code]);
                } else {
                    $href = LaravelLocalization::getLocalizedURL($code, null, [], true);
                }
            @endphp
            <a href="{{ $href }}"
               rel="alternate" hreflang="{{ $code }}"
               class="{{ $code === $locale ? 'active' : '' }}">
                <span>{{ $info['native'] ?? $info['name'] ?? strtoupper($code) }}</span>
                <span class="dot"></span>
            </a>
        @endforeach
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
