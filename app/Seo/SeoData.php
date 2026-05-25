<?php

namespace App\Seo;

final readonly class SeoData
{
    /**
     * @param  array<string,string>  $alternates  hreflang code (e.g. 'uk', 'en', 'x-default') => absolute URL
     * @param  list<array<string,mixed>>  $jsonLd  list of JSON-LD graphs to emit on the page
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public string $ogType = 'website',
        public ?string $ogImage = null,
        public ?int $ogImageWidth = null,
        public ?int $ogImageHeight = null,
        public array $alternates = [],
        public string $robots = 'index,follow',
        public array $jsonLd = [],
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
    ) {}
}
