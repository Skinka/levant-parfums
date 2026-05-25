<?php

namespace App\Seo\Builders;

use App\Models\Content\Page;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;
use Illuminate\Support\Str;

final class PageSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Page $page, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $page->getTranslation('seo_title', $locale),
            (string) $page->getTranslation('title', $locale),
        );

        $description = $this->buildDescription(
            (string) $page->getTranslation('seo_description', $locale),
            (string) $page->getTranslation('intro', $locale),
            (string) $page->getTranslation('content', $locale),
        );

        $alternates = $page->is_homepage
            ? $this->resolver->forStaticRoute('/')
            : $this->resolver->forTranslatedSlug('/', $page->getTranslations('slug'));

        $canonical = $alternates[$locale] ?? $alternates['x-default'] ?? (string) config('app.url');

        $ogImage = $this->resolveOgImage($page);

        $jsonLd = [];
        if (! $page->is_homepage) {
            $jsonLd[] = BreadcrumbSchema::generate([
                ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                ['name' => (string) $page->getTranslation('title', $locale), 'url' => $canonical],
            ]);
        }

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'website',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter($jsonLd)),
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

    private function resolveOgImage(Page $page): string
    {
        $media = $page->getFirstMedia('primary');
        $base = rtrim((string) config('app.url'), '/');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        $fallback = (string) config('site.seo.default_og_image', '/images/og/default.jpg');

        return $base.'/'.ltrim($fallback, '/');
    }
}
