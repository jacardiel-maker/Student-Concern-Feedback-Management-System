@php
    $viewer = auth()->user();
    $nextStatus = $concern->status->next();
    $canAdvance = $viewer !== null
        && $viewer->can('manage', $concern)
        && $nextStatus !== null;
@endphp

@if ($canAdvance)
    <form method="POST" action="{{ route('concerns.status.update', $concern) }}" class="inline-flex">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="{{ $nextStatus->value }}">
        <button
            type="submit"
            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            title="{{ __('labels.mark_as', ['status' => $nextStatus->label()]) }}"
            aria-label="{{ __('labels.mark_as', ['status' => $nextStatus->label()]) }}"
        >
            &rarr;
        </button>
    </form>
@endif
