@extends('layouts.app')

@section('pageTitle', __('labels.my_concerns'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-spruce-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ config('app.name') }}</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.my_concerns') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ __('labels.my_concerns_help') }}</p>
            </div>
            <a href="{{ route('concerns.create') }}" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.new_concern') }}
            </a>
        </div>

        <form method="GET" action="{{ route('concerns.index') }}" class="grid gap-4 border-l-4 border-sage-300 bg-white/70 px-4 py-4 sm:grid-cols-4 sm:px-5">
            <div>
                <label for="filter-status" class="block text-xs font-medium text-slate-600">{{ __('labels.status') }}</label>
                <select id="filter-status" name="status" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach (\App\Enums\ConcernStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="block text-xs font-medium text-slate-600">{{ __('labels.category') }}</label>
                <select id="filter-category" name="category" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected(request('category') == $categoryOption->id)>{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="filter-search" class="block text-xs font-medium text-slate-600">{{ __('labels.search') }}</label>
                <div class="mt-1.5 flex gap-2">
                    <input id="filter-search" name="search" type="search" value="{{ request('search') }}" placeholder="{{ __('labels.search') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
                </div>
            </div>
        </form>

        <div data-ticket-list-wrap>
            @include('partials.ticket_skeleton')

            <section data-ticket-list aria-label="{{ __('labels.my_concerns') }}" class="border-y border-spruce-100 bg-white/60">
            @forelse ($concerns as $concern)
                @php
                    $isUrgent = $concern->priority === \App\Enums\ConcernPriority::Urgent || $concern->category?->is_urgent;
                    $isSettled = $concern->status === \App\Enums\ConcernStatus::Resolved;
                @endphp

                <article @class([
                    'grid gap-4 border-b border-spruce-100 px-4 py-5 last:border-b-0 sm:grid-cols-[10rem_1fr_auto] sm:items-center sm:px-5',
                    'bg-clay-50/70' => $isUrgent,
                    'bg-sage-50/70' => $isSettled && ! $isUrgent,
                ])>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('concerns.show', $concern) }}" class="font-mono text-sm font-semibold text-spruce-700 hover:underline">
                            {{ $concern->ticket_no }}
                        </a>
                        @if ($isUrgent)
                            <span class="urgent-pulse rounded-full bg-clay-100 px-2 py-0.5 text-[11px] font-semibold text-clay-800">
                                {{ __('labels.priority_urgent') }}
                            </span>
                        @endif
                        @if ($concern->isSlaBreached())
                            <span class="rounded-full bg-clay-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                                {{ __('labels.sla_breached') }}
                            </span>
                        @endif
                        @if ($concern->isAnonymous())
                            <span class="rounded-full bg-sage-100 px-2 py-0.5 text-[11px] font-medium text-sage-800">
                                {{ __('labels.anonymous') }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <a href="{{ route('concerns.show', $concern) }}" class="font-serif text-lg font-semibold text-slate-900 hover:text-spruce-700">
                            {{ $concern->subject }}
                        </a>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            <span>{{ $concern->category?->name ?? __('labels.unknown') }}</span>
                            <span aria-hidden="true">&middot;</span>
                            <span>{{ $concern->created_at?->format('M d, Y') }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                        @include('partials.status_pill')
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

            @include('partials.pagination', ['paginator' => $concerns])
        </div>
    </div>
@endsection
