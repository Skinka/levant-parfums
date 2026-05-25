<?php

namespace App\Seo\Builders;

final readonly class CatalogSeoInput
{
    public function __construct(
        public bool $hasSortParam,
        public bool $hasSeriesParam,
        public int $page = 1,
    ) {}
}
