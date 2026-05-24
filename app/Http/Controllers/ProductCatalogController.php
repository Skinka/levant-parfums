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
