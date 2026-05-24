@extends('layouts.site', ['theme' => $theme])

@section('title', $product->name . ' · LEVANT Parfums')
@section('description', $product->tagline ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
    <div class="product-page">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('catalogue.public.crumb_home')],
                ['href' => route('products.index'), 'label' => __('catalogue.public.title')],
                ['href' => route('products.index', ['series' => $product->series?->slug]),
                 'label' => $product->series?->name ?? ''],
                ['label' => $product->name],
            ]"/>

            <div class="top">
                <x-site.product-gallery :product="$product"/>
                <x-site.product-info :product="$product"/>
            </div>

            @if($product->notes->isNotEmpty())
                <x-site.product-pyramid :product="$product"/>
            @endif
        </div>
    </div>
@endsection
