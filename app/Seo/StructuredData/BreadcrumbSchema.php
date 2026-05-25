<?php

namespace App\Seo\StructuredData;

final class BreadcrumbSchema
{
    /**
     * @param  list<array{name:string,url:string}>  $crumbs
     * @return array<string,mixed>
     */
    public static function generate(array $crumbs): array
    {
        if ($crumbs === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $crumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ],
                $crumbs,
                array_keys($crumbs),
            ),
        ];
    }
}
