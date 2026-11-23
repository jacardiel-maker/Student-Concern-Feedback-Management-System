@php
    $workflowStatuses = \App\Enums\ConcernStatus::cases();
    $currentIndex = array_search($concern->status, $workflowStatuses, true);
@endphp

<div class="flex items-center gap-1" role="img" aria-label="{{ __('labels.workflow_position', ['status' => $concern->status->label()]) }}">
    @foreach ($workflowStatuses as $index => $status)
        @if ($index > 0)
            <span @class([
                'h-px w-4',
                'bg-spruce-500' => $index <= $currentIndex,
                'bg-sage-200' => $index > $currentIndex,
            ])></span>
        @endif

        <span @class([
            'flex h-4 w-4 items-center justify-center rounded-full border text-[9px] font-bold leading-none',
            'border-spruce-500 bg-spruce-500 text-white' => $index === $currentIndex,
            'border-spruce-500 bg-sage-100 text-spruce-700' => $index < $currentIndex,
            'border-sage-300 bg-white text-transparent' => $index > $currentIndex,
        ])>
            {{ $index < $currentIndex ? '✓' : '•' }}
        </span>
    @endforeach
</div>
