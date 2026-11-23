@extends('layouts.app')

@section('pageTitle', __('labels.unmask'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.unmask') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('labels.anonymous') }}</p>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ticket_no') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.subject') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.category') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.submitted_at') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($concerns as $concern)
                            <tr>
                                <td class="px-5 py-3">@include('partials.ticket_badges', ['ticketRoute' => 'admin.unmask.show'])</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $concern->subject }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $concern->created_at?->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.unmask.show', $concern) }}" class="font-medium text-spruce-700 hover:underline">{{ __('labels.details') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $concerns])
        </section>
    </div>
@endsection
