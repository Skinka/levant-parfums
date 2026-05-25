<?php

namespace App\Http\Controllers;

use App\Models\Catalogue\Product;
use App\Seo\Builders\CatalogSeoBuilder;
use App\Seo\Builders\CatalogSeoInput;
use App\Seo\Builders\ProductSeoBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    private const PER_PAGE = 8;

    private const ALLOWED_SERIES = ['onyx', 'luxury'];

    private const ALLOWED_SORTS = ['pop', 'new', 'priceA', 'priceB'];

    public function __construct(
        private readonly CatalogSeoBuilder $catalogSeoBuilder,
        private readonly ProductSeoBuilder $productSeoBuilder,
    ) {}

    public function index(Request $request): View
    {
        $rawSeries = $request->query('series');
        $series = in_array($rawSeries, self::ALLOWED_SERIES, true) ? $rawSeries : null;

        $rawSort = $request->query('sort', 'pop');
        $sort = in_array($rawSort, self::ALLOWED_SORTS, true) ? $rawSort : 'pop';

        $base = Product::query()
            ->where('is_published', true)
            ->when($series, fn (Builder $q) => $q->whereHas(
                'series',
                fn (Builder $s) => $s->where('slug', $series)
            ));

        $list = (clone $base)
            ->with(['series', 'perfumeFamily', 'tags', 'media']);

        $this->applySort($list, $sort);

        $products = $list->paginate(self::PER_PAGE)->withQueryString();

        $total = (clone $base)->count();
        $totalAll = Product::where('is_published', true)->count();

        $seo = $this->catalogSeoBuilder->build(
            new CatalogSeoInput(
                hasSortParam: $request->has('sort'),
                hasSeriesParam: $request->has('series'),
                page: max(1, $request->integer('page', 1)),
            ),
            app()->getLocale(),
        );

        return view('products.index', [
            'products' => $products,
            'total' => $total,
            'totalAll' => $totalAll,
            'series' => $series,
            'sort' => $sort,
            'seo' => $seo,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_published, 404);

        $product->load(['series', 'perfumeFamily', 'concentration', 'notes', 'tags', 'occasions', 'media']);

        $sameSeries = Product::query()
            ->where('is_published', true)
            ->where('series_id', $product->series_id)
            ->where('id', '!=', $product->id)
            ->with(['series', 'perfumeFamily', 'tags', 'media'])
            ->take(6)
            ->get();

        if ($sameSeries->count() < 4 && $product->series_id) {
            $need = 6 - $sameSeries->count();
            $cross = Product::query()
                ->where('is_published', true)
                ->where('series_id', '!=', $product->series_id)
                ->where('id', '!=', $product->id)
                ->with(['series', 'perfumeFamily', 'tags', 'media'])
                ->take($need)
                ->get();
            $related = $sameSeries->concat($cross);
        } else {
            $related = $sameSeries;
        }

        $theme = $product->series?->theme_class ?? 'theme-cream';

        $seo = $this->productSeoBuilder->build($product, app()->getLocale());

        return view('products.show', compact('product', 'related', 'theme', 'seo'));
    }

    private function applySort(Builder $query, string $sort): void
    {
        $tagPredicate = fn (string $slug) => "EXISTS (
            SELECT 1 FROM product_tag pt
            JOIN tags t ON t.id = pt.tag_id
            WHERE pt.product_id = products.id AND t.slug = '{$slug}'
        )";

        match ($sort) {
            'new' => $query
                ->orderByRaw($tagPredicate('new').' DESC')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            'priceA' => $query
                ->orderBy('price_uah')
                ->orderBy('id'),
            'priceB' => $query
                ->orderByDesc('price_uah')
                ->orderBy('id'),
            default => $query
                ->orderByRaw($tagPredicate('bestseller').' DESC')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };
    }
}
