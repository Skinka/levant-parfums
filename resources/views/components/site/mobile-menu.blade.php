@props(['nav', 'path', 'locale'])

@php
    $supported = LaravelLocalization::getSupportedLocales();
    $alternateSlugs = view()->shared('alternateSlugs', []);
@endphp

<div
    x-data="{ open: false }"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    @keydown.escape.window="open = false"
    class="mobile-menu-root"
>
    <button
        type="button"
        class="mobile-menu-toggle"
        :class="{ 'is-open': open }"
        :aria-expanded="open"
        aria-controls="mobile-menu-panel"
        aria-label="{{ __('site.menu.toggle_aria') }}"
        @click="open = !open"
    >
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>

    <div
        id="mobile-menu-panel"
        class="mobile-menu"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('site.menu.aria') }}"
        x-show="open"
        x-cloak
        x-transition.opacity.duration.250ms
    >
        <nav class="mobile-menu-nav" aria-label="{{ __('site.nav.aria') }}">
            @foreach($nav as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="{{ $item['match']($path) ? 'active' : '' }}"
                    @click="open = false"
                >
                    {{ __("site.nav.{$item['key']}") }}
                </a>
            @endforeach
        </nav>

        <div class="mobile-menu-lang">
            <span class="mobile-menu-lang-label">{{ __('site.menu.language') }}</span>
            <div class="mobile-menu-lang-row">
                @foreach($supported as $code => $info)
                    @php
                        if (! empty($alternateSlugs) && ! empty($alternateSlugs[$code])) {
                            $href = url('/'.$code.'/'.$alternateSlugs[$code]);
                        } else {
                            $href = LaravelLocalization::getLocalizedURL($code, null, [], true);
                        }
                    @endphp
                    <a
                        href="{{ $href }}"
                        rel="alternate"
                        hreflang="{{ $code }}"
                        class="{{ $code === $locale ? 'active' : '' }}"
                    >
                        {{ strtoupper($code === 'uk' ? 'UA' : $code) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</div>
