@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="button-row">
        @if (!$paginator->onFirstPage())
            <a class="button outline" href="{{ $paginator->previousPageUrl() }}">← Previous</a>
        @endif
        <span>
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="button outline" href="{{ $paginator->nextPageUrl() }}">Next →</a>
        @endif
    </nav>
@endif
