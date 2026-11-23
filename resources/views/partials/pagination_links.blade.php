@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">
            @if ($paginator->firstItem())
                {{ __('Showing') }} {{ $paginator->firstItem() }} {{ __('to') }} {{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }}
            @else
                {{ $paginator->total() }} {{ __('results') }}
            @endif
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-spruce-500 hover:bg-spruce-50 hover:text-spruce-700 focus:ring-2 focus:ring-spruce-500">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-8 w-8 items-center justify-center text-sm text-slate-400" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-spruce-600 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-transparent px-2 text-sm font-medium text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 hover:text-slate-900 focus:ring-2 focus:ring-spruce-500">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-spruce-500 hover:bg-spruce-50 hover:text-spruce-700 focus:ring-2 focus:ring-spruce-500">&rsaquo;</a>
            @else
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="{{ __('pagination.next') }}">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
