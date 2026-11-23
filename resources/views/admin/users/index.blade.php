@extends('layouts.app')

@section('pageTitle', __('labels.users'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.users') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.overview') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.trashed') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('labels.trashed_users') }}
                </a>
                <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                    {{ __('labels.create') }}
                </a>
            </div>
        </div>

        @if ($pendingApprovals > 0)
            <a href="{{ route('admin.users.index', ['approval' => 'pending']) }}" class="flex flex-wrap items-center justify-between gap-3 border-l-4 border-clay-500 bg-clay-50/70 px-5 py-3 transition hover:bg-clay-50">
                <span class="text-sm font-semibold text-clay-900">{{ __('labels.pending_approvals_notice') }}</span>
                <span class="rounded-full bg-clay-100 px-2.5 py-1 text-xs font-semibold text-clay-800">{{ $pendingApprovals }} {{ __('labels.awaiting_approval') }}</span>
            </a>
        @endif

        <div class="flex flex-wrap items-center gap-2 text-sm">
            @php
                $baseFilters = request()->only(['search', 'role']);
            @endphp
            <a href="{{ route('admin.users.index', $baseFilters) }}" class="rounded-full px-3 py-1.5 font-medium {{ $pendingOnly ? 'text-slate-600 hover:bg-slate-100' : 'bg-spruce-600 text-white' }}">{{ __('labels.all') }}</a>
            <a href="{{ route('admin.users.index', $baseFilters + ['approval' => 'pending']) }}" class="rounded-full px-3 py-1.5 font-medium {{ $pendingOnly ? 'bg-clay-100 text-clay-900' : 'text-slate-600 hover:bg-slate-100' }}">{{ __('labels.pending_approvals') }} ({{ $pendingApprovals }})</a>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-[240px] flex-1">
                <label for="filter-search" class="block text-xs font-medium text-slate-600">{{ __('labels.search') }}</label>
                <input id="filter-search" name="search" type="search" value="{{ request('search') }}" placeholder="{{ __('labels.search') }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div class="min-w-[180px]">
                <label for="filter-role" class="block text-xs font-medium text-slate-600">{{ __('labels.role') }}</label>
                <select id="filter-role" name="role" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.email') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.role') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.department') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->student_number)
                                        <span class="block text-xs font-normal text-slate-500">{{ $user->student_number }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->role?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->department ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($user->isAwaitingApproval())
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900">{{ __('labels.awaiting_approval') }}</span>
                                    @else
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $user->is_active ? 'bg-sage-100 text-spruce-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $user->is_active ? __('labels.active') : __('labels.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        @if ($user->isAwaitingApproval())
                                            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                @csrf
                                                <button type="submit" class="font-semibold text-spruce-700 hover:underline">{{ __('labels.approve') }}</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-spruce-700 hover:underline">{{ __('labels.edit') }}</a>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST"
                                                  action="{{ route('admin.users.destroy', $user) }}"
                                                  data-confirm
                                                  data-confirm-title="{{ __('labels.confirm_deactivate_title') }}"
                                                  data-confirm-body="{{ __('labels.confirm_deactivate_body', ['name' => $user->name]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-clay-700 hover:underline">{{ __('labels.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $users])
        </section>
    </div>
@endsection
