<?php

namespace App\Seo\Builders;

use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;

final class ArticleIndexSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(string $locale, int $page = 1): SeoData
    {
        $query = $page > 1 ? ['page' => $page] : [];
        $alternates = $this->resolver->forStaticRoute('/articles', $query);
        $canonical = $alternates[$locale];

        $title = (string) trans('site.articles.meta_title');
        $description = (string) trans('site.articles.meta_description');
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        return new SeoData(
            title: str_contains($title, $suffix) ? $title : $title.' · '.$suffix,
            description: $description !== '' ? $description : null,
            canonical: $canonical,
            ogType: 'website',
            ogImage: rtrim((string) config('app.url'), '/').'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/'),
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([
                BreadcrumbSchema::generate([
                    ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                    ['name' => $title, 'url' => $canonical],
                ]),
            ])),
        );
    }
}
