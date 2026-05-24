@extends('layouts.site')

@section('title', __('site.articles.meta_title'))
@section('description', __('site.articles.meta_description'))

@section('content')
    <section style="padding: 32px 0 120px">
        <div class="container">
            <h1>{{ __('site.articles.title') }}</h1>

            @foreach($articles as $article)
                <a href="{{ route('articles.show', $article->getTranslation('slug', app()->getLocale())) }}">
                    {{ $article->title }}
                </a>
            @endforeach

            {{ $articles->onEachSide(1)->links('vendor.pagination.site') }}
        </div>
    </section>
@endsection
