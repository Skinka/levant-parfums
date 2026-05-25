<?php

namespace App\Seo;

final class AlternateUrlResolver
{
    private const DEFAULT_LOCALE = 'uk';

    private const SUPPORTED = ['uk', 'en'];

    /**
     * Build hreflang map for a translated-slug route (Page, Article).
     *
     * @param  string  $pathPrefix  e.g. "/" for /{slug}, "/articles/" for /articles/{slug}.
     * @param  array<string,?string>  $slugs  locale => translated slug (null = no translation)
     * @return array<string,string>
     */
    public function forTranslatedSlug(string $pathPrefix, array $slugs): array
    {
        $result = [];

        foreach (self::SUPPORTED as $locale) {
            $slug = $slugs[$locale] ?? null;
            if ($slug === null || $slug === '') {
                continue;
            }
            $result[$locale] = $this->buildUrl($locale, $pathPrefix.$slug);
        }

        if (isset($result[self::DEFAULT_LOCALE])) {
            $result['x-default'] = $result[self::DEFAULT_LOCALE];
        }

        return $result;
    }

    /**
     * Build hreflang map for a shared-slug route (Product — slug identical across locales).
     *
     * @return array<string,string>
     */
    public function forSharedSlug(string $path): array
    {
        $result = [];
        foreach (self::SUPPORTED as $locale) {
            $result[$locale] = $this->buildUrl($locale, $path);
        }
        $result['x-default'] = $result[self::DEFAULT_LOCALE];

        return $result;
    }

    /**
     * Build hreflang map for a static route (home, /products, /articles).
     *
     * @param  array<string,scalar>  $queryParams
     * @return array<string,string>
     */
    public function forStaticRoute(string $path, array $queryParams = []): array
    {
        $query = $queryParams === [] ? '' : '?'.http_build_query($queryParams);

        $result = [];
        foreach (self::SUPPORTED as $locale) {
            $result[$locale] = $this->buildUrl($locale, $path).$query;
        }
        $result['x-default'] = $result[self::DEFAULT_LOCALE];

        return $result;
    }

    private function buildUrl(string $locale, string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if ($locale === self::DEFAULT_LOCALE) {
            return $base.$path;
        }

        // Non-default locale: insert `/en` prefix. Avoid double slashes when path is "/".
        if ($path === '/') {
            return $base.'/'.$locale;
        }

        return $base.'/'.$locale.$path;
    }
}
