<?php

namespace App\Http\Controllers;

use App\Models\Content\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->paginate(12);

        return view('articles.index', compact('articles'));
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

        return view('articles.show', compact('article', 'products', 'related'));
    }
}
