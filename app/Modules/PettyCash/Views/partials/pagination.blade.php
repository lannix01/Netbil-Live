@if ($paginator->hasPages())
    <nav class="petty-pager" role="navigation" aria-label="Pagination Navigation">
        @if ($paginator->onFirstPage())
            <span class="petty-pager-btn is-disabled" aria-disabled="true">Prev</span>
        @else
            <a class="petty-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
        @endif

        <div class="petty-page-list">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="petty-page-gap">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="petty-page-link is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="petty-page-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="petty-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="petty-pager-btn is-disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif

