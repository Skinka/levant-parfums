<?php

namespace App\Seo\StructuredData;

use App\Models\Catalogue\Product;
use Illuminate\Support\Str;

final class ProductSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(Product $product, string $locale, string $canonical, ?string $ogImage): array
    {
        $currency = $locale === 'uk' ? 'UAH' : 'EUR';
        $price = $locale === 'uk' ? (string) $product->price_uah : (string) $product->price_eur;
        $availability = $product->in_stock
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $product->getTranslation('name', $locale),
            'description' => Str::limit(
                trim(strip_tags((string) $product->getTranslation('description', $locale))),
                500,
                ''
            ),
            'sku' => (string) $product->id,
            'brand' => ['@type' => 'Brand', 'name' => (string) config('site.organization.name', 'LEVANT Parfums')],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => $currency,
                'price' => $price,
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        if ($ogImage !== null && $ogImage !== '') {
            $data['image'] = [$ogImage];
        }

        $family = $product->perfumeFamily;
        if ($family !== null) {
            $category = (string) $family->getTranslation('name', $locale);
            if ($category !== '') {
                $data['category'] = $category;
            }
        }

        return $data;
    }
}
