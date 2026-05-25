@props(['seo' => null, 'locale' => 'uk'])

@php
    $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');
    $base = rtrim((string) config('app.url'), '/');
    $defaultOg = $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');

    $title       = $seo?->title ?? $suffix;
    $description = $seo?->description;
    $canonical   = $seo?->canonical ?? $base.request()->getPathInfo();
    $robots      = $seo?->robots ?? 'index,follow';
    $ogType      = $seo?->ogType ?? 'website';
    $ogImage     = $seo?->ogImage ?? $defaultOg;
    $ogImageW    = $seo?->ogImageWidth ?? 1200;
    $ogImageH    = $seo?->ogImageHeight ?? 630;
    $alternates  = $seo?->alternates ?? [];
    $jsonLd      = $seo?->jsonLd ?? [];
    $ogLocale    = $locale === 'uk' ? 'uk_UA' : 'en_GB';
    $ogLocaleAlt = $locale === 'uk' ? 'en_GB' : 'uk_UA';
@endphp

<title>{{ $title }}</title>
@if($description)
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

@foreach($alternates as $hreflang => $url)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
@endforeach

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
@if($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $suffix }}">
<meta property="og:locale" content="{{ $ogLocale }}">
<meta property="og:locale:alternate" content="{{ $ogLocaleAlt }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="{{ $ogImageW }}">
<meta property="og:image:height" content="{{ $ogImageH }}">

@if($ogType === 'article' && $seo?->publishedTime)
    <meta property="article:published_time" content="{{ $seo->publishedTime }}">
    @if($seo->modifiedTime)
        <meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
    @endif
@endif

<meta name="twitter:card" content="{{ config('site.seo.twitter_card', 'summary_large_image') }}">
<meta name="twitter:title" content="{{ $title }}">
@if($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
<meta name="twitter:image" content="{{ $ogImage }}">

@foreach($jsonLd as $schema)
    <x-site.json-ld :data="$schema" />
@endforeach
