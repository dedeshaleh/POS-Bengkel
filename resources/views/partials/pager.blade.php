@if ($paginator->hasPages())
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px;flex-wrap:wrap">
        <div class="muted" style="font-size:12px">
            Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            @if ($paginator->onFirstPage())
                <span class="btn secondary" style="opacity:.5;pointer-events:none">Prev</span>
            @else
                <a class="btn secondary" href="{{ $paginator->previousPageUrl() }}">Prev</a>
            @endif

            <span class="muted" style="font-size:12px">Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="btn secondary" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="btn secondary" style="opacity:.5;pointer-events:none">Next</span>
            @endif
        </div>
    </div>
@endif
