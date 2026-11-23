<div data-confirm-modal
     class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="confirm-modal-title">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <h2 id="confirm-modal-title" data-confirm-title class="font-serif text-xl font-semibold text-spruce-800">
            {{ __('labels.confirm') }}
        </h2>
        <p data-confirm-body class="mt-3 text-sm leading-relaxed text-slate-600"></p>
        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" data-confirm-cancel class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                {{ __('labels.cancel') }}
            </button>
            <button type="button" data-confirm-accept class="rounded-lg bg-clay-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-clay-700">
                {{ __('labels.confirm') }}
            </button>
        </div>
    </div>
</div>
