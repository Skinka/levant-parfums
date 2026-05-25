@extends('layouts.site')

@section('title', $page->seo_title ?: $page->title)
@if($page->seo_description)
    @section('description', $page->seo_description)
@endif

@section('content')
    <div class="landing-page">
        @foreach($page->visibleBlocks() as $block)
            @includeIf("pages.blocks.{$block['type']}", [
                'data' => $block['data'],
                'page' => $page,
            ])
        @endforeach
    </div>
@endsection
