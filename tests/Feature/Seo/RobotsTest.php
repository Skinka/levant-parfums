<?php

beforeEach(fn () => config(['app.url' => 'https://example.test']));

it('serves robots.txt with admin disallow and sitemap reference', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('text/plain');

    $body = $response->getContent();
    expect($body)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: https://example.test/sitemap.xml');
});
