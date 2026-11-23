<div data-ticket-skeleton class="border-y border-spruce-100 bg-white/60" aria-hidden="true">
    @for ($placeholder = 0; $placeholder < 3; $placeholder++)
        <div class="grid gap-4 border-b border-spruce-100 px-4 py-5 last:border-b-0 sm:grid-cols-[10rem_1fr_auto] sm:items-center sm:px-5">
            <div class="flex items-center gap-2">
                <div class="skeleton-shimmer h-4 w-28 rounded"></div>
            </div>
            <div class="space-y-2">
                <div class="skeleton-shimmer h-5 w-2/3 rounded"></div>
                <div class="skeleton-shimmer h-3 w-1/3 rounded"></div>
            </div>
            <div class="flex items-center justify-end gap-3">
                <div class="skeleton-shimmer h-6 w-20 rounded-full"></div>
                <div class="skeleton-shimmer h-4 w-16 rounded"></div>
            </div>
        </div>
    @endfor
</div>
