@php($currentLocale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LEVANT Parfums')</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    @fonts
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
