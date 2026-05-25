<?php

namespace App\Http\Controllers;

use App\Models\Content\Page;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    public function home()
    {
        $page = Page::query()->homepage()->published()->firstOrFail();

        return view("pages.templates.{$page->template->value}", ['page' => $page]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $page = Page::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        View::share('alternateSlugs', $page->getTranslations('slug'));

        return view("pages.templates.{$page->template->value}", ['page' => $page]);
    }
}
