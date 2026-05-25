<?php

namespace App\Http\Controllers;

use App\Models\Content\Article;
use App\Seo\Builders\ArticleIndexSeoBuilder;
use App\Seo\Builders\ArticleSeoBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleIndexSeoBuilder $indexSeoBuilder,
        private readonly ArticleSeoBuilder $showSeoBuilder,
    ) {}

    public function index(Request $request)
    {
        $articles = Article::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->paginate(12);

        $seo = $this->indexSeoBuilder->build(app()->getLocale(), max(1, $request->integer('page', 1)));

        return view('articles.index', compact('articles', 'seo'));
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $article = Article::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        $products = $article->products()
            ->with(['media', 'tags', 'series', 'perfumeFamily'])
            ->get();

        $related = Article::query()
            ->published()
            ->with('media')
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        View::share('alternateSlugs', $article->getTranslations('slug'));

        $seo = $this->showSeoBuilder->build($article, $locale);

        return view('articles.show', compact('article', 'products', 'related', 'seo'));
    }
}
