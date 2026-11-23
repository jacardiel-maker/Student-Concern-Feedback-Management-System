@php
    use App\Enums\ConcernStatus;

    $workflowStatuses = ConcernStatus::cases();
    $lastIndex = count($workflowStatuses) - 1;
    $currentIndex = array_search($concern->status, $workflowStatuses, true);

    $statusChange = session('status_change');
    $statusJustChanged = is_array($statusChange)
        && ($statusChange['concern_id'] ?? null) === $concern->id
        && ($statusChange['to'] ?? null) === $concern->status->value;

    $previousStatus = $statusJustChanged ? ConcernStatus::tryFrom($statusChange['from'] ?? '') : null;
    $previousIndex = $previousStatus !== null
        ? array_search($previousStatus, $workflowStatuses, true)
        : $currentIndex;

    $targetProgress = $lastIndex > 0 ? ($currentIndex / $lastIndex) * 100 : 100;
    $initialProgress = $statusJustChanged && $lastIndex > 0
        ? ($previousIndex / $lastIndex) * 100
        : $targetProgress;
@endphp

<section
    aria-labelledby="workflow-heading"
    data-workflow-stepper
    data-advancing="{{ $statusJustChanged ? 'true' : 'false' }}"
    data-target-progress="{{ $targetProgress }}"
    class="rounded-2xl border border-spruce-100 bg-white/80 px-5 py-5 sm:px-6"
>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 id="workflow-heading" class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.workflow') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('labels.workflow_help') }}</p>
        </div>
        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-sage-600">
            {{ __('labels.step_of', ['current' => $currentIndex + 1, 'total' => count($workflowStatuses)]) }}
        </span>
    </div>

    <div class="relative mt-7">
        <div class="absolute left-4 right-4 top-3 h-0.5 bg-sage-200" aria-hidden="true">
            <div class="workflow-fill h-full bg-spruce-500" style="width: {{ $initialProgress }}%"></div>
        </div>

        <ol class="relative flex justify-between gap-1">
            @foreach ($workflowStatuses as $index => $status)
                <li class="flex min-w-0 flex-1 flex-col items-center text-center">
                    <span @class([
                        'relative z-10 flex h-6 w-6 items-center justify-center rounded-full border text-[11px] font-bold transition-colors',
                        'border-spruce-500 bg-sage-100 text-spruce-700' => $index < $currentIndex,
                        'border-spruce-600 bg-spruce-600 text-white ring-4 ring-spruce-100' => $index === $currentIndex,
                        'border-sage-300 bg-white text-transparent' => $index > $currentIndex,
                        'status-settle' => $index === $currentIndex && $statusJustChanged,
                    ])>
                        {{ $index < $currentIndex ? '✓' : '•' }}
                    </span>
                    <span @class([
                        'mt-2 max-w-[6rem] text-[11px] leading-tight',
                        'font-semibold text-spruce-700' => $index === $currentIndex,
                        'text-slate-500' => $index !== $currentIndex,
                    ])>
                        {{ $status->label() }}
                    </span>
                </li>
            @endforeach
        </ol>
    </div>
</section>
