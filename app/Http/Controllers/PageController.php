<?php

namespace App\Http\Controllers;

use App\Models\Content\Page;
use App\Seo\Builders\PageSeoBuilder;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    public function __construct(private readonly PageSeoBuilder $seoBuilder) {}

    public function home()
    {
        $page = Page::query()->homepage()->published()->firstOrFail();
        $seo = $this->seoBuilder->build($page, app()->getLocale());

        return view("pages.templates.{$page->template->value}", ['page' => $page, 'seo' => $seo]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $page = Page::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        View::share('alternateSlugs', $page->getTranslations('slug'));

        $seo = $this->seoBuilder->build($page, $locale);

        return view("pages.templates.{$page->template->value}", ['page' => $page, 'seo' => $seo]);
    }
}
