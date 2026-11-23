<div class="flex flex-col items-center justify-center gap-3 py-6">
    <svg class="h-14 w-14 text-slate-300" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M8 22.5 32 39l24-16.5V17a4 4 0 0 0-4-4H12a4 4 0 0 0-4 4v5.5Z" fill="currentColor" opacity=".18"/>
        <path d="M8 23v23a4 4 0 0 0 4 4h40a4 4 0 0 0 4-4V23L32 39 8 23Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M8 23 32 6l24 17-24 16L8 23Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <circle cx="45" cy="44" r="12" fill="#2f6f63"/>
        <path d="m39.5 44 3.8 3.8L51 40" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <p class="text-sm text-slate-500">{{ $message ?? __('messages.no_results') }}</p>
</div>
