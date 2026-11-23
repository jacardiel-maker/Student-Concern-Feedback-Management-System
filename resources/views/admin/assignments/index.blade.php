@extends('layouts.app')

@section('pageTitle', __('labels.assignments'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.assignments') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('labels.overview') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.assignments.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div>
                <label for="scope" class="block text-xs font-medium text-slate-600">{{ __('labels.scope') }}</label>
                <select id="scope" name="scope" class="mt-1.5 rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="open" @selected(request('scope', 'open') === 'open')>{{ __('labels.open_tickets') }}</option>
                    <option value="unassigned" @selected(request('scope') === 'unassigned')>{{ __('labels.unassigned') }}</option>
                    <option value="all" @selected(request('scope') === 'all')>{{ __('labels.all') }}</option>
                </select>
            </div>
            <div class="min-w-[220px] flex-1">
                <label for="search" class="block text-xs font-medium text-slate-600">{{ __('labels.search') }}</label>
                <input id="search" name="search" type="search" value="{{ request('search') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
        </form>

        @if ($suggestions->isNotEmpty())
            <section class="rounded-xl border border-spruce-100 bg-spruce-50 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-spruce-700">{{ __('labels.assignment_suggestions') }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($suggestions as $suggestion)
                        <span class="inline-flex items-center gap-2 rounded-full border border-spruce-100 bg-white px-3 py-1.5 text-xs text-slate-600">
                            <span class="font-medium text-slate-900">{{ $suggestion->category?->name ?? __('labels.unknown') }}</span>
                            <span aria-hidden="true">&rarr;</span>
                            <span>{{ $suggestion->recipient?->name ?? __('labels.unknown') }}</span>
                            <span class="rounded-full bg-spruce-100 px-2 py-0.5 font-semibold text-spruce-700">{{ $suggestion->handled_count }}</span>
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ticket_no') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.subject') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.author') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.category') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.assigned_to') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($concerns as $concern)
                            <tr class="{{ $concern->recipient_id ? '' : 'bg-amber-50' }}">
                                <td class="px-5 py-3">@include('partials.ticket_badges')</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $concern->subject }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->submitterLabelFor(auth()->user()) }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    <form method="POST" action="{{ route('admin.assignments.store', $concern) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <label class="sr-only" for="recipient_id-{{ $concern->id }}">{{ __('labels.recipient') }}</label>
                                        <select id="recipient_id-{{ $concern->id }}" name="recipient_id" required class="min-w-[180px] rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                                            <option value="">{{ $concern->recipient ? $concern->recipient->name : __('labels.general_office') }}</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" @selected((string) old('recipient_id') === (string) $teacher->id)>{{ $teacher->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-spruce-600 px-3 py-2 text-xs font-semibold text-white hover:bg-spruce-700">
                                            {{ __('labels.save') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($concern->recipient)
                                        <form method="POST" action="{{ route('admin.assignments.destroy', $concern) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-clay-700 hover:underline">{{ __('labels.unassigned') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs font-medium text-amber-800">{{ __('labels.unassigned') }}</span>
                                    @endif
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
