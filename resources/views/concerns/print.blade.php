@extends('layouts.app')

@section('pageTitle', __('labels.print_ticket') . ' ' . $concern->ticket_no)

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('concerns.show', $concern) }}" class="text-sm font-medium text-spruce-700 hover:underline">{{ __('labels.back') }}</a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.save_as_pdf') }}
            </button>
        </div>

        <article class="rounded-xl border border-slate-300 bg-white p-8 text-slate-900 shadow-sm">
            <header class="flex items-start justify-between gap-6 border-b border-slate-200 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ config('app.name') }}</p>
                    <h1 class="mt-1 font-mono text-2xl font-bold text-slate-900">{{ $concern->ticket_no }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ __('labels.print_ticket_help') }}</p>
                </div>
                <div class="shrink-0 text-center">
                    <div class="mx-auto h-[180px] w-[180px] [&>svg]:h-full [&>svg]:w-full">{!! $qrSvg !!}</div>
                    <p class="mt-2 max-w-[180px] break-all font-mono text-[10px] text-slate-500">{{ $ticketUrl }}</p>
                </div>
            </header>

            <dl class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.subject') }}</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $concern->subject }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.category') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->category?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.status') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->status->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.priority') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->priority->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.author') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->submitterLabelFor(auth()->user()) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.assigned_to') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->recipient?->name ?? __('labels.unassigned') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.submitted_at') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->created_at?->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.updated_at') }}</dt>
                    <dd class="mt-1 text-sm">{{ $concern->status_changed_at?->format('M d, Y H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.description') }}</dt>
                <dd class="mt-2 whitespace-pre-line rounded-lg bg-slate-50 p-4 text-sm leading-relaxed">{{ $concern->description }}</dd>
            </div>
        </article>
    </div>
@endsection
