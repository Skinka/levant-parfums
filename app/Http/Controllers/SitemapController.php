<?php

namespace App\Http\Controllers;

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use App\Models\Content\Page;
use App\Seo\AlternateUrlResolver;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $entries = $this->buildEntries();

            return view('sitemap.index', ['entries' => $entries])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @return list<array{loc:string,lastmod:?string,alternates:array<string,string>}>
     */
    private function buildEntries(): array
    {
        $now = Carbon::now()->toIso8601String();
        $entries = [];

        // Home and section index pages: static routes, both locales, x-default = uk.
        foreach (['/', '/products', '/articles'] as $path) {
            $alts = $this->resolver->forStaticRoute($path);
            $entries[] = ['loc' => $alts['uk'], 'lastmod' => $now, 'alternates' => $alts];
        }

        // CMS pages: translated slug.
        Page::query()
            ->where('is_published', true)
            ->get(['id', 'is_homepage', 'slug', 'updated_at'])
            ->each(function (Page $page) use (&$entries) {
                if ($page->is_homepage) {
                    return; // home is already covered by the static-route block above
                }
                $alts = $this->resolver->forTranslatedSlug('/', $page->getTranslations('slug'));
                if ($alts === []) {
                    return;
                }
                $loc = $alts['x-default'] ?? reset($alts);
                $entries[] = ['loc' => $loc, 'lastmod' => $page->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        // Products: shared slug, both locales always present.
        Product::query()
            ->where('is_published', true)
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Product $product) use (&$entries) {
                $alts = $this->resolver->forSharedSlug('/products/'.$product->slug);
                $entries[] = ['loc' => $alts['x-default'], 'lastmod' => $product->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        // Articles: translated slug.
        Article::query()
            ->published()
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Article $article) use (&$entries) {
                $alts = $this->resolver->forTranslatedSlug('/articles/', $article->getTranslations('slug'));
                if ($alts === []) {
                    return;
                }
                $loc = $alts['x-default'] ?? reset($alts);
                $entries[] = ['loc' => $loc, 'lastmod' => $article->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        return $entries;
    }
}
