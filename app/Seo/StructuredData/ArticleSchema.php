<?php

namespace App\Seo\StructuredData;

use App\Models\Content\Article;
use Illuminate\Support\Str;

final class ArticleSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(Article $article, string $locale, string $canonical, ?string $ogImage): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $orgName = (string) config('site.organization.name', 'LEVANT Parfums');
        $logoPath = (string) config('site.organization.logo', '/images/og/logo.png');
        $logoUrl = str_starts_with($logoPath, 'http') ? $logoPath : $base.'/'.ltrim($logoPath, '/');

        $description = Str::limit(
            trim(strip_tags((string) $article->getTranslation('intro', $locale))),
            300,
            ''
        );

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string) $article->getTranslation('title', $locale),
            'description' => $description,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $orgName],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $orgName,
                'logo' => ['@type' => 'ImageObject', 'url' => $logoUrl],
            ],
            'mainEntityOfPage' => $canonical,
            'inLanguage' => $locale === 'uk' ? 'uk-UA' : 'en-GB',
        ];

        if ($ogImage !== null && $ogImage !== '') {
            $data['image'] = $ogImage;
        }

        return $data;
    }
}
