@if ($paginator->hasPages())
    @php($prevLabel = __('catalogue.public.prev'))
    @php($nextLabel = __('catalogue.public.next'))

    <nav class="pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="arrow disabled" aria-disabled="true">← {{ $prevLabel }}</span>
        @else
            <a class="arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev">← {{ $prevLabel }}</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="gap">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="arrow" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ $nextLabel }} →</a>
        @else
            <span class="arrow disabled" aria-disabled="true">{{ $nextLabel }} →</span>
        @endif
    </nav>
@endif
