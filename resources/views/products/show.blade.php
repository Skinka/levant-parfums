@extends('layouts.site', ['theme' => $theme])

@section('title', $product->name . ' · LEVANT Parfums')

@section('content')
    <div class="product-page">
        <div class="container">
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->tagline }}</p>
        </div>
    </div>
@endsection
