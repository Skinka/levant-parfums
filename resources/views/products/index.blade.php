@extends('layouts.site')

@section('title', __('catalogue.public.title').' · LEVANT Parfums')
@section('description', __('catalogue.public.subtitle'))

@php
    $filters = [
        ['key' => 'all',    'label' => __('catalogue.public.filter_all'),    'value' => null],
        ['key' => 'onyx',   'label' => __('catalogue.public.filter_onyx'),   'value' => 'onyx'],
        ['key' => 'luxury', 'label' => __('catalogue.public.filter_luxury'), 'value' => 'luxury'],
    ];
    $sortKeys = ['pop', 'new', 'priceA', 'priceB'];
@endphp

@section('content')
    <div class="catalog">
        <div class="page-head catalog-head">
            <div class="container">
                <x-site.breadcrumbs :items="[
                    ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('catalogue.public.crumb_home')],
                    ['label' => __('catalogue.public.title')],
                ]"/>

                <div class="row">
                    <div>
                        <div class="eyebrow">{{ __('catalogue.public.eyebrow') }}</div>
                        <h1 style="margin-top: 16px">{{ __('catalogue.public.title') }}</h1>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                        <div class="eyebrow">{{ __('catalogue.public.total_label') }}</div>
                        <div style="font-family: var(--font-serif); font-size: 72px; color: var(--accent); line-height: 1;">
                            {{ $total }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="catalog-filters">
                <div class="chip-row">
                    @foreach($filters as $filter)
                        @php
                            $isActive = ($filter['value'] === null && $series === null)
                                || ($filter['value'] !== null && $series === $filter['value']);
                            $url = $filter['value'] === null
                                ? route('products.index', request()->except(['series', 'page']))
                                : route('products.index', array_merge(request()->except(['page']), ['series' => $filter['value']]));
                        @endphp
                        <a href="{{ $url }}" class="chip {{ $isActive ? 'active' : '' }}">
                            {{ $filter['label'] }}
                        </a>
                    @endforeach
                </div>

                <form method="get" action="{{ route('products.index') }}" class="sort">
                    @if($series)<input type="hidden" name="series" value="{{ $series }}">@endif
                    <label for="catalog-sort">{{ __('catalogue.public.sort_label') }}</label>
                    <select name="sort" id="catalog-sort" onchange="this.form.submit()">
                        @foreach($sortKeys as $key)
                            <option value="{{ $key }}" {{ $sort === $key ? 'selected' : '' }}>
                                {{ __("catalogue.public.sort.$key") }}
                            </option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm">OK</button></noscript>
                </form>
            </div>

            @if($products->isEmpty())
                <div class="catalog-empty">{{ __('catalogue.public.empty') }}</div>
            @else
                <div class="product-grid reveal-stagger">
                    @foreach($products as $product)
                        <x-site.product-card :product="$product" />
                    @endforeach
                </div>

                {{ $products->links('vendor.pagination.site') }}
            @endif

            <p class="lead" style="margin-top: 24px">{{ __('catalogue.public.subtitle') }}</p>
        </div>
    </div>
@endsection
