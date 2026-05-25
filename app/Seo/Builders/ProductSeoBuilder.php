<?php

namespace App\Seo\Builders;

use App\Models\Catalogue\Product;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;
use App\Seo\StructuredData\ProductSchema;
use Illuminate\Support\Str;

final class ProductSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Product $product, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $product->getTranslation('seo_title', $locale),
            (string) $product->getTranslation('name', $locale),
        );

        $description = $this->buildDescription(
            (string) $product->getTranslation('seo_description', $locale),
            (string) $product->getTranslation('tagline', $locale),
            (string) $product->getTranslation('description', $locale),
        );

        $alternates = $this->resolver->forSharedSlug('/products/'.$product->slug);
        $canonical = $alternates[$locale];
        $ogImage = $this->resolveOgImage($product);

        $productSchema = ProductSchema::generate($product, $locale, $canonical, $ogImage);
        $breadcrumb = BreadcrumbSchema::generate([
            ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
            ['name' => (string) trans('catalogue.public.title'), 'url' => $this->resolver->forStaticRoute('/products')[$locale]],
            ['name' => (string) $product->getTranslation('name', $locale), 'url' => $canonical],
        ]);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'product',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([$productSchema, $breadcrumb])),
        );
    }

    private function buildTitle(string $seoTitle, string $name): string
    {
        $base = $seoTitle !== '' ? $seoTitle : $name;
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        if ($base === '' || str_contains($base, $suffix)) {
            return $base !== '' ? $base : $suffix;
        }

        return $base.' · '.$suffix;
    }

    private function buildDescription(string $seoDescription, string $tagline, string $description): ?string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }
        if ($tagline !== '') {
            return $tagline;
        }
        if ($description === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($description)), 160);
    }

    private function resolveOgImage(Product $product): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $media = $product->getFirstMedia('primary');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        return $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');
    }
}
