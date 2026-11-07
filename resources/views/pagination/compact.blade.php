@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pg pg-disabled">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pg">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg pg-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg pg-active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pg">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pg">&rsaquo;</a>
        @else
            <span class="pg pg-disabled">&rsaquo;</span>
        @endif
    </nav>
@endif