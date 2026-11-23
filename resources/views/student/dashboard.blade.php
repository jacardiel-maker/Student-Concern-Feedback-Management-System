@extends('layouts.app')

@section('pageTitle', __('labels.dashboard'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-spruce-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ __('labels.student_portal') }}</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.overview') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ __('labels.my_concerns_help') }}</p>
            </div>
            <a href="{{ route('concerns.create') }}" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.new_concern') }}
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @include('partials.stat_card', ['label' => __('labels.total'), 'value' => $stats['total'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.pending_count'), 'value' => $stats['pending'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.in_progress'), 'value' => $stats['in_progress'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.resolved_count'), 'value' => $stats['resolved'] ?? 0])
        </div>

        <section class="border-l-4 border-sage-400 bg-sage-50/80 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.my_concerns') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('labels.workflow_help') }}</p>
                </div>
                <a href="{{ route('concerns.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                    {{ __('labels.view_all') }}
                </a>
            </div>
        </section>

        <section aria-label="{{ __('labels.recent_concerns') }}" class="border-y border-spruce-100 bg-white/60">
            @forelse ($recentConcerns as $concern)
                @php
                    $isUrgent = $concern->priority === \App\Enums\ConcernPriority::Urgent || $concern->category?->is_urgent;
                    $isSettled = $concern->status === \App\Enums\ConcernStatus::Resolved;
                @endphp

                <article @class([
                    'grid gap-3 border-b border-spruce-100 px-4 py-4 last:border-b-0 sm:grid-cols-[11rem_1fr_auto] sm:items-center sm:px-5',
                    'bg-clay-50/70' => $isUrgent,
                    'bg-sage-50/70' => $isSettled && ! $isUrgent,
                ])>
                    @include('partials.ticket_badges')

                    <div class="min-w-0">
                        <a href="{{ route('concerns.show', $concern) }}" class="font-serif text-base font-semibold text-slate-900 hover:text-spruce-700">
                            {{ $concern->subject }}
                        </a>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $concern->category?->name ?? __('labels.unknown') }}
                            <span aria-hidden="true">&middot;</span>
                            {{ $concern->created_at?->format('M d, Y H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                        @include('partials.mini_timeline')
                        <a href="{{ route('concerns.show', $concern) }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                            {{ __('labels.details') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-6">@include('partials.empty_state')</div>
            @endforelse
        </section>
    </div>
@endsection
