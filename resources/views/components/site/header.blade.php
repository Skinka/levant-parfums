@props(['locale'])

@php
    $philosophySlug = config('content.philosophy_slug')[$locale] ?? config('content.philosophy_slug')['uk'];
    $philosophyUrl = route('page.show', ['slug' => $philosophySlug]);

    $nav = [
        ['key' => 'home',       'url' => LaravelLocalization::localizeURL('/'),         'match' => fn ($r) => $r === '/' || $r === ''],
        ['key' => 'catalog',    'url' => route('products.index'),                       'match' => fn ($r) => str_starts_with($r, '/products')],
        ['key' => 'philosophy', 'url' => $philosophyUrl,                                'match' => fn ($r) => $r === '/' . $philosophySlug],
        ['key' => 'articles',   'url' => route('articles.index', [], false),            'match' => fn ($r) => str_starts_with($r, '/articles')],
    ];
    $path = '/' . trim(request()->path(), '/');
    // Strip locale prefix from the path so the matcher works regardless of locale URL prefixing.
    foreach (config('catalogue.locales', []) as $loc) {
        if (str_starts_with($path, "/$loc/") || $path === "/$loc") {
            $path = '/' . trim(substr($path, strlen("/$loc")), '/');
            break;
        }
    }
@endphp

<header class="header">
    <div class="container">
        <a href="{{ LaravelLocalization::localizeURL('/') }}" class="brand" aria-label="LEVANT">
            <span class="mark">L E V A N T</span>
            <span class="sub">{{ __('site.brand_strapline') }}</span>
        </a>

        <nav class="nav" aria-label="{{ __('site.nav.aria') }}">
            @foreach($nav as $item)
                <a href="{{ $item['url'] }}"
                   class="{{ $item['match']($path) ? 'active' : '' }}">
                    {{ __("site.nav.{$item['key']}") }}
                </a>
            @endforeach
        </nav>

        <div class="head-right">
            <x-site.lang-switch :locale="$locale" />
        </div>
    </div>
</header>
