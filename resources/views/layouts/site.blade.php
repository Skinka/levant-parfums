@php($currentLocale = app()->getLocale())
@php($seo = $seo ?? null)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-site.seo-meta :seo="$seo" :locale="$currentLocale" />
    <x-site.json-ld :data="\App\Seo\StructuredData\OrganizationSchema::generate()" />
    <x-site.json-ld :data="\App\Seo\StructuredData\WebSiteSchema::generate($currentLocale)" />

    @fonts
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="{{ $theme ?? 'theme-cream' }}">
    <x-site.intro-veil />
    <x-site.announcement />
    <x-site.header :locale="$currentLocale" />

    <main class="page-fade">
        @yield('content')
    </main>

    <x-site.footer :locale="$currentLocale" />

    @stack('scripts')
</body>
</html>
