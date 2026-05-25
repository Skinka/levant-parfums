<?php

namespace App\Seo\Builders;

use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;

final class CatalogSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(CatalogSeoInput $input, string $locale): SeoData
    {
        $isFiltered = $input->hasSortParam || $input->hasSeriesParam;
        $query = $input->page > 1 ? ['page' => $input->page] : [];
        $alternates = $this->resolver->forStaticRoute('/products', $query);
        $canonical = $alternates[$locale];

        $robots = $isFiltered ? 'noindex,follow' : 'index,follow';

        $title = (string) trans('catalogue.public.title');
        $description = (string) trans('catalogue.public.subtitle');
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        $base = rtrim((string) config('app.url'), '/');
        $ogImage = $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');

        return new SeoData(
            title: str_contains($title, $suffix) ? $title : $title.' · '.$suffix,
            description: $description !== '' ? $description : null,
            canonical: $canonical,
            ogType: 'website',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: $robots,
            jsonLd: array_values(array_filter([
                BreadcrumbSchema::generate([
                    ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                    ['name' => $title, 'url' => $this->resolver->forStaticRoute('/products')[$locale]],
                ]),
            ])),
        );
    }
}
