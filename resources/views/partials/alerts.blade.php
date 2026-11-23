@if (session('success') || session('error') || $errors->any())
    <div class="pointer-events-none fixed inset-x-3 top-3 z-50 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-5 sm:top-5 sm:w-96"
         aria-live="polite"
         aria-atomic="false">
        @if (session('success'))
            <div data-toast class="toast-enter pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-spruce-200 bg-spruce-50/95 px-4 py-3 text-sm text-spruce-800 shadow-lg backdrop-blur" role="status">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-spruce-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9 10.94 7.28 9.22a.75.75 0 1 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.06 0l4.25-4.25Z" clip-rule="evenodd"/>
                </svg>
                <span class="flex-1">{{ session('success') }}</span>
                <button type="button" data-toast-dismiss class="text-spruce-600 hover:text-spruce-800" aria-label="{{ __('labels.close') }}">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div data-toast class="toast-enter pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-clay-200 bg-clay-50/95 px-4 py-3 text-sm text-clay-800 shadow-lg backdrop-blur" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-clay-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V6Zm-1 8.5a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z" clip-rule="evenodd"/>
                </svg>
                <span class="flex-1">{{ session('error') }}</span>
                <button type="button" data-toast-dismiss class="text-clay-600 hover:text-clay-800" aria-label="{{ __('labels.close') }}">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div data-toast class="toast-enter pointer-events-auto w-full rounded-xl border border-clay-200 bg-clay-50/95 px-4 py-3 text-sm text-clay-800 shadow-lg backdrop-blur" role="alert">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-clay-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V6Zm-1 8.5a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="font-semibold">{{ __('messages.validation_failed') }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" data-toast-dismiss class="text-clay-600 hover:text-clay-800" aria-label="{{ __('labels.close') }}">&times;</button>
                </div>
            </div>
        @endif
    </div>
@endif
