@extends('pages.layouts.base')

@section('content')
    <article>
        <h1>{{ $page->title }}</h1>
        {!! Str::markdown($page->content ?? '') !!}
    </article>
@endsection
