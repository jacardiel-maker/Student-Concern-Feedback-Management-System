@extends('layouts.app')

@section('pageTitle', __('labels.categories'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.categories') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.overview') }}</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.create') }}
            </a>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.name_fil') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.priority') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $category->name_fil ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($category->is_urgent)
                                        <span class="rounded-full bg-clay-100 px-2.5 py-1 text-xs font-medium text-clay-800">{{ __('labels.priority_urgent') }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ __('labels.priority_normal') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $category->is_active ? 'bg-sage-100 text-spruce-700' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $category->is_active ? __('labels.active') : __('labels.inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="font-medium text-spruce-700 hover:underline">{{ __('labels.edit') }}</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-clay-700 hover:underline">{{ __('labels.delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $categories])
        </section>
    </div>
@endsection
