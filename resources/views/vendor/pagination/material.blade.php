@if ($paginator->hasPages())
<nav class="md-pagination" style="margin-top:4px;">
    @if ($paginator->onFirstPage())
        <span class="md-pagination__item md-pagination__item--disabled">&laquo;</span>
    @else
        <a class="md-pagination__item" href="{{ $paginator->previousPageUrl() }}">&laquo;</a>
    @endif
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="md-pagination__item">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="md-pagination__item md-pagination__item--active">{{ $page }}</span>
                @else
                    <a class="md-pagination__item" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())
        <a class="md-pagination__item" href="{{ $paginator->nextPageUrl() }}">&raquo;</a>
    @else
        <span class="md-pagination__item md-pagination__item--disabled">&raquo;</span>
    @endif
</nav>
@endif
