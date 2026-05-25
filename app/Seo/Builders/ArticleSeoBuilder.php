<?php

namespace App\Seo\Builders;

use App\Models\Content\Article;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\ArticleSchema;
use App\Seo\StructuredData\BreadcrumbSchema;
use Illuminate\Support\Str;

final class ArticleSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Article $article, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $article->getTranslation('seo_title', $locale),
            (string) $article->getTranslation('title', $locale),
        );

        $description = $this->buildDescription(
            (string) $article->getTranslation('seo_description', $locale),
            (string) $article->getTranslation('intro', $locale),
            (string) $article->getTranslation('content', $locale),
        );

        $alternates = $this->resolver->forTranslatedSlug('/articles/', $article->getTranslations('slug'));
        $canonical = $alternates[$locale] ?? $alternates['x-default'] ?? (string) config('app.url');
        $ogImage = $this->resolveOgImage($article);

        $articleSchema = ArticleSchema::generate($article, $locale, $canonical, $ogImage);
        $breadcrumb = BreadcrumbSchema::generate([
            ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
            ['name' => (string) trans('site.articles.meta_title'), 'url' => $this->resolver->forStaticRoute('/articles')[$locale]],
            ['name' => (string) $article->getTranslation('title', $locale), 'url' => $canonical],
        ]);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'article',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([$articleSchema, $breadcrumb])),
            publishedTime: $article->published_at?->toIso8601String(),
            modifiedTime: $article->updated_at?->toIso8601String(),
        );
    }

    private function buildTitle(string $seoTitle, string $title): string
    {
        $base = $seoTitle !== '' ? $seoTitle : $title;
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        if ($base === '' || str_contains($base, $suffix)) {
            return $base !== '' ? $base : $suffix;
        }

        return $base.' · '.$suffix;
    }

    private function buildDescription(string $seoDescription, string $intro, string $content): ?string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }
        $source = $intro !== '' ? $intro : $content;
        if ($source === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($source)), 160);
    }

    private function resolveOgImage(Article $article): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $media = $article->getFirstMedia('primary');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        return $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');
    }
}
