<?php

namespace App\Http\Controllers;

use App\Models\Catalogue\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    private const PER_PAGE = 8;

    private const ALLOWED_SERIES = ['onyx', 'luxury'];

    private const ALLOWED_SORTS = ['pop', 'new', 'priceA', 'priceB'];

    public function index(Request $request): View
    {
        $series = $request->query('series');
        $series = in_array($series, self::ALLOWED_SERIES, true) ? $series : null;

        $sort = $request->query('sort', 'pop');
        $sort = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'pop';

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

        return view('products.index', [
            'products' => $products,
            'total' => $total,
            'totalAll' => $totalAll,
            'series' => $series,
            'sort' => $sort,
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

        return view('products.show', compact('product', 'related', 'theme'));
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
