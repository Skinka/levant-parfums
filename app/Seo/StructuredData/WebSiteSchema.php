<?php

namespace App\Seo\StructuredData;

final class WebSiteSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(string $locale): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => $base.'/',
            'name' => (string) config('site.organization.name', 'LEVANT Parfums'),
            'inLanguage' => $locale === 'uk' ? 'uk-UA' : 'en-GB',
        ];
    }
}
