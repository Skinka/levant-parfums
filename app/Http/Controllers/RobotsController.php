<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $body = <<<TXT
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/*

Sitemap: {$sitemapUrl}
TXT;

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
