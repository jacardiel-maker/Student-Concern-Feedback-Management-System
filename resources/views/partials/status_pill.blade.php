@php
    $animateStatus = $animateStatus ?? false;

    $statusPillClass = match ($concern->status->value) {
        'pending' => 'bg-amber-100 text-amber-900',
        'under_review' => 'bg-sage-100 text-spruce-700',
        'in_progress' => 'bg-spruce-100 text-spruce-800',
        'resolved' => 'bg-sage-200 text-sage-800',
        'closed' => 'bg-slate-200 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span @class([
    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
    $statusPillClass,
    'status-settle' => $animateStatus,
])>
    {{ $concern->status->label() }}
</span>
