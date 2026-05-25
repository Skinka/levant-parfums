<?php

namespace App\Seo\StructuredData;

final class OrganizationSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(): array
    {
        $org = (array) config('site.organization');
        $base = rtrim((string) config('app.url'), '/');
        $logo = (string) ($org['logo'] ?? '');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) ($org['name'] ?? ''),
            'url' => $base.'/',
            'logo' => self::absolutize($logo, $base),
        ];

        if (! empty($org['email'])) {
            $data['email'] = $org['email'];
        }
        if (! empty($org['phone'])) {
            $data['telephone'] = $org['phone'];
        }

        $address = $org['address'] ?? [];
        $addressFields = array_filter([
            'addressCountry' => $address['country'] ?? null,
            'addressLocality' => $address['locality'] ?? null,
            'streetAddress' => $address['street'] ?? null,
        ]);
        if ($addressFields !== []) {
            $data['address'] = ['@type' => 'PostalAddress', ...$addressFields];
        }

        $sameAs = array_values(array_filter((array) ($org['same_as'] ?? [])));
        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    private static function absolutize(string $path, string $base): string
    {
        if ($path === '') {
            return $base.'/';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $base.'/'.ltrim($path, '/');
    }
}
