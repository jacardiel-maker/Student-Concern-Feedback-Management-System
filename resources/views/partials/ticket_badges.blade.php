@php
    $ticketRoute = $ticketRoute ?? 'concerns.show';
    $isUrgent = $concern->priority === \App\Enums\ConcernPriority::Urgent || $concern->category?->is_urgent;
    $statusJustChanged = session('status_change.concern_id') === $concern->id;
@endphp

<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route($ticketRoute, $concern) }}" class="font-mono text-sm font-semibold text-spruce-700 hover:underline">
        {{ $concern->ticket_no }}
    </a>

    @include('partials.status_pill', ['animateStatus' => $statusJustChanged])

    <span @class([
        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
        'urgent-pulse bg-clay-100 text-clay-800' => $isUrgent,
        'bg-slate-100 text-slate-700' => ! $isUrgent,
    ])>
        {{ $concern->priority->label() }}
    </span>

    @if ($concern->isSlaBreached())
        <span class="inline-flex rounded-full bg-clay-600 px-2.5 py-1 text-xs font-semibold text-white">
            {{ __('labels.sla_breached') }}
        </span>
    @endif

    @if ($concern->isAnonymous())
        <span class="inline-flex rounded-full bg-sage-100 px-2.5 py-1 text-xs font-medium text-sage-800">
            {{ __('labels.anonymous') }}
        </span>
    @endif
</div>
