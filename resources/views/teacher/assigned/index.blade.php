@extends('layouts.app')

@section('pageTitle', __('labels.assigned_concerns'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.assigned_concerns') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('labels.overview') }}</p>
        </div>

        <form method="GET" action="{{ route('teacher.assigned.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-6">
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
                        <option value="{{ $categoryOption->id }}" @selected((string) request('category') === (string) $categoryOption->id)>{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-priority" class="block text-xs font-medium text-slate-600">{{ __('labels.priority') }}</label>
                <select id="filter-priority" name="priority" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach (\App\Enums\ConcernPriority::cases() as $priorityOption)
                        <option value="{{ $priorityOption->value }}" @selected(request('priority') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-from" class="block text-xs font-medium text-slate-600">{{ __('labels.from') }}</label>
                <input id="filter-from" name="from" type="date" value="{{ request('from') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div>
                <label for="filter-to" class="block text-xs font-medium text-slate-600">{{ __('labels.to') }}</label>
                <input id="filter-to" name="to" type="date" value="{{ request('to') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div>
                <label for="filter-search" class="block text-xs font-medium text-slate-600">{{ __('labels.search') }}</label>
                <div class="mt-1.5 flex gap-2">
                    <input id="filter-search" name="search" type="search" value="{{ request('search') }}" placeholder="{{ __('labels.search') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
                </div>
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ticket_no') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.subject') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.author') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.category') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.submitted_at') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($concerns as $concern)
                            <tr class="{{ $concern->priority === \App\Enums\ConcernPriority::Urgent ? 'bg-clay-50 hover:bg-clay-100' : 'hover:bg-slate-50' }}">
                                <td class="px-5 py-3">@include('partials.ticket_badges', ['ticketRoute' => 'teacher.assigned.show'])</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $concern->subject }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->submitterLabelFor(auth()->user()) }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->created_at?->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        @include('partials.concern_advance_action', ['concern' => $concern])
                                        <a href="{{ route('teacher.assigned.show', $concern) }}" class="font-medium text-spruce-700 hover:underline">{{ __('labels.details') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $concerns])
        </section>
    </div>
@endsection
