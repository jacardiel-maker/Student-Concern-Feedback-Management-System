@extends('layouts.app')

@section('pageTitle', __('labels.dashboard'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-spruce-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ __('labels.teacher_portal') }}</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.overview') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ __('labels.urgent_queue_help') }}</p>
            </div>
            <a href="{{ route('teacher.assigned.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                {{ __('labels.assigned_concerns') }}
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @include('partials.stat_card', ['label' => __('labels.assigned_concerns'), 'value' => $stats['assigned'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.pending_count'), 'value' => $stats['pending'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.open_count'), 'value' => $stats['open'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.resolved_count'), 'value' => $stats['resolved'] ?? 0])
        </div>

        <section class="border-l-4 border-clay-400 bg-clay-50/60">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="font-serif text-lg font-semibold text-clay-900">{{ __('labels.urgent_queue') }}</h2>
                    <p class="mt-1 text-xs text-clay-700">{{ __('labels.urgent_queue_help') }}</p>
                </div>
                <span class="rounded-full bg-clay-100 px-2.5 py-1 text-xs font-semibold text-clay-800">
                    {{ $urgentQueue->count() }}
                </span>
            </div>

            <div class="border-t border-clay-200 bg-white/70">
                @forelse ($urgentQueue as $concern)
                    <article class="grid gap-3 border-b border-clay-100 px-5 py-4 last:border-b-0 lg:grid-cols-[11rem_1fr_12rem_auto] lg:items-center">
                        @include('partials.ticket_badges', ['ticketRoute' => 'teacher.assigned.show'])

                        <div class="min-w-0">
                            <p class="font-serif text-base font-semibold text-slate-900">{{ $concern->subject }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $concern->created_at?->format('M d, Y H:i') }}</p>
                        </div>

                        <p class="text-sm text-slate-600">{{ $concern->submitterLabelFor(auth()->user()) }}</p>

                        <div class="flex flex-wrap items-center gap-3">
                            @include('partials.concern_advance_action', ['concern' => $concern])
                            <a href="{{ route('teacher.assigned.show', $concern) }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                                {{ __('labels.details') }}
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-6">@include('partials.empty_state')</div>
                @endforelse
            </div>
        </section>

        <section class="border-l-4 border-spruce-400 bg-sage-50/70">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.awaiting_first_response') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('labels.awaiting_first_response_help') }}</p>
                </div>
                <span class="rounded-full bg-sage-200 px-2.5 py-1 text-xs font-semibold text-spruce-700">
                    {{ $awaitingResponse->count() }}
                </span>
            </div>

            <div class="border-t border-sage-200 bg-white/60">
                @forelse ($awaitingResponse as $concern)
                    <article class="grid gap-3 border-b border-sage-100 px-5 py-4 last:border-b-0 lg:grid-cols-[11rem_1fr_12rem_auto] lg:items-center">
                        @include('partials.ticket_badges', ['ticketRoute' => 'teacher.assigned.show'])

                        <div class="min-w-0">
                            <p class="font-serif text-base font-semibold text-slate-900">{{ $concern->subject }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $concern->category?->name ?? __('labels.unknown') }}</p>
                        </div>

                        <p class="text-sm text-slate-600">{{ $concern->submitterLabelFor(auth()->user()) }}</p>

                        <div class="flex flex-wrap items-center gap-3">
                            @include('partials.concern_advance_action', ['concern' => $concern])
                            <a href="{{ route('teacher.assigned.show', $concern) }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                                {{ __('labels.details') }}
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-6">@include('partials.empty_state')</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
