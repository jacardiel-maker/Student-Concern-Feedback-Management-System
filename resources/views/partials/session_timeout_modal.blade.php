<div data-session-timeout-modal
     data-keep-alive-url="{{ route('session.keep-alive') }}"
     data-csrf-token="{{ csrf_token() }}"
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="session-timeout-title">
    <div class="w-full max-w-md rounded-2xl border border-spruce-200 bg-white p-6 shadow-2xl">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sage-100 text-lg" aria-hidden="true">&#9200;</div>
        <h2 id="session-timeout-title" class="mt-4 font-serif text-xl font-semibold text-spruce-800">
            {{ __('labels.session_timeout_title') }}
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('labels.session_timeout_warning') }}</p>
        <div class="mt-6 flex justify-end">
            <button type="button" data-session-stay class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.stay_signed_in') }}
            </button>
        </div>
    </div>
</div>
