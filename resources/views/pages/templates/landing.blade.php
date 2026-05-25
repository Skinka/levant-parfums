@extends('layouts.site')

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
