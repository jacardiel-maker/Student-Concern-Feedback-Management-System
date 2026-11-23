@extends('layouts.app')

@section('pageTitle', __('labels.trashed_users'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.trashed_users') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.trashed_users_help') }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                {{ __('labels.users') }}
            </a>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.email') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.role') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->role?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->deleted_at?->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-spruce-700 hover:underline">{{ __('labels.restore') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $users])
        </section>
    </div>
@endsection
