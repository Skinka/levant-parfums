@props(['items' => []])

<nav class="crumbs" aria-label="breadcrumb">
    @foreach($items as $i => $item)
        @if($i > 0)<span class="sep">/</span>@endif
        @if(!empty($item['href']))
            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @else
            <span class="current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
