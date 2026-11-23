@extends('layouts.app')

@section('pageTitle', __('labels.audit_logs'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.audit_logs') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('labels.overview') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
            <div>
                <label for="filter-action" class="block text-xs font-medium text-slate-600">{{ __('labels.action') }}</label>
                <select id="filter-action" name="action" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-concern" class="block text-xs font-medium text-slate-600">{{ __('labels.ticket_no') }}</label>
                <input id="filter-concern" name="concern" type="text" value="{{ request('concern') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div>
                <label for="filter-search" class="block text-xs font-medium text-slate-600">{{ __('labels.search') }}</label>
                <input id="filter-search" name="search" type="search" value="{{ request('search') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.action') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.users') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ticket_no') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.old_value') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.new_value') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.reason') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ip_address') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="{{ $log->action === 'identity_unmasked' ? 'bg-clay-50' : '' }}">
                                <td class="px-5 py-3 text-slate-600">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $log->action }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($log->concern)
                                        <a href="{{ route('concerns.show', $log->concern) }}" class="font-mono text-spruce-700 hover:underline">{{ $log->concern->ticket_no }}</a>
                                    @else
                                        <span class="text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="max-w-[220px] px-5 py-3 text-xs">@include('partials.audit_value', ['value' => $log->old_value])</td>
                                <td class="max-w-[220px] px-5 py-3 text-xs">@include('partials.audit_value', ['value' => $log->new_value])</td>
                                <td class="px-5 py-3 text-slate-600">{{ $log->reason ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $logs])
        </section>
    </div>
@endsection
