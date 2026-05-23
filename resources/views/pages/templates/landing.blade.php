@extends('pages.layouts.base')

@section('content')
    @foreach($page->visibleBlocks() as $block)
        @includeIf("pages.blocks.{$block['type']}", [
            'data' => $block['data'],
            'page' => $page,
        ])
    @endforeach
@endsection
