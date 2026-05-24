@extends('layouts.site')

@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))

@section('content')
    <article style="padding: 32px 0 80px">
        <div class="container">
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->intro }}</p>
        </div>
    </article>
@endsection
