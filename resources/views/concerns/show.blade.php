@extends('layouts.app')

@section('pageTitle', __('labels.ticket_no') . ' ' . $concern->ticket_no)

@section('content')
    @php
        $currentUser = auth()->user();
        $isStaff = $currentUser->isTeacher() || $currentUser->isAdmin();
        $backRoute = $currentUser->isAdmin()
            ? 'admin.reports.index'
            : ($currentUser->isTeacher() ? 'teacher.assigned.index' : 'concerns.index');
        $nextStatus = $concern->status->next();
        $newReplyId = session('reply_posted');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-spruce-100 pb-5">
            <div>
                <nav aria-label="{{ __('labels.back') }}" class="flex flex-wrap items-center gap-2 text-sm">
                    <a href="{{ route($backRoute) }}" class="font-medium text-spruce-700 hover:underline">
                        {{ $currentUser->isAdmin() ? __('labels.reports') : ($currentUser->isTeacher() ? __('labels.assigned_concerns') : __('labels.my_concerns')) }}
                    </a>
                    <span aria-hidden="true" class="text-slate-400">&rsaquo;</span>
                    <span class="font-mono font-medium text-slate-600">{{ $concern->ticket_no }}</span>
                </nav>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ $concern->subject }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @include('partials.ticket_badges')
                <a href="{{ route('concerns.print', $concern) }}" target="_blank" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('labels.print_ticket') }}
                </a>
                @if ($currentUser->isAdmin() && $concern->isAnonymous() && ($concern->category?->is_urgent || $concern->priority === \App\Enums\ConcernPriority::Urgent))
                    <a href="{{ route('admin.unmask.show', $concern) }}" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                        {{ __('labels.reveal_identity') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="no-print sticky top-16 z-10 -mx-4 border-y border-spruce-100 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <span class="font-mono text-sm font-semibold text-spruce-700">{{ $concern->ticket_no }}</span>
                    @include('partials.status_pill', ['animateStatus' => false])
                    @if ($concern->priority === \App\Enums\ConcernPriority::Urgent || $concern->category?->is_urgent)
                        <span class="urgent-pulse rounded-full bg-clay-100 px-2 py-0.5 text-[11px] font-semibold text-clay-800">
                            {{ __('labels.priority_urgent') }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="#conversation" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('labels.reply') }}
                    </a>
                    <a href="{{ route('concerns.print', $concern) }}" target="_blank" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('labels.print_ticket') }}
                    </a>
                    @if ($isStaff && $nextStatus !== null)
                        <form method="POST"
                              action="{{ route('concerns.status.update', $concern) }}"
                              @if ($nextStatus === \App\Enums\ConcernStatus::Closed)
                                  data-confirm
                                  data-confirm-title="{{ __('labels.confirm_close_title') }}"
                                  data-confirm-body="{{ __('labels.confirm_close_body', ['ticket' => $concern->ticket_no]) }}"
                              @endif>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="rounded-lg bg-spruce-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-spruce-700">
                                {{ __('labels.mark_as', ['status' => $nextStatus->label()]) }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @include('partials.workflow_stepper', ['concern' => $concern])

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.details') }}</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.ticket_no') }}</dt>
                            <dd class="mt-0.5 font-mono text-slate-900">{{ $concern->ticket_no }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.subject') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->subject }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.category') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->category?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.author') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->submitterLabelFor($currentUser) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.recipient') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->recipient?->name ?? __('labels.unassigned') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.submitted_at') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->created_at?->format('M d, Y H:i') }}</dd>
                        </div>
                        @if ($concern->resolved_at)
                            <div>
                                <dt class="font-medium text-slate-500">{{ __('labels.resolved_at') }}</dt>
                                <dd class="mt-0.5 text-slate-900">{{ $concern->resolved_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                        @if ($concern->closed_at)
                            <div>
                                <dt class="font-medium text-slate-500">{{ __('labels.closed_at') }}</dt>
                                <dd class="mt-0.5 text-slate-900">{{ $concern->closed_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <p class="text-sm font-medium text-slate-500">{{ __('labels.description') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ $concern->description }}</p>
                    </div>
                </section>

                @php($concernAttachments = $concern->attachments->whereNull('reply_id'))
                @if ($concernAttachments->isNotEmpty())
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.attachments') }}</h2>
                        <ul class="mt-4 divide-y divide-slate-100">
                            @foreach ($concernAttachments as $attachment)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                                    <a href="{{ route('attachments.download', $attachment) }}" class="font-medium text-spruce-700 hover:underline">
                                        {{ $attachment->original_name }}
                                    </a>
                                    <span class="text-xs text-slate-500">{{ number_format(($attachment->size_bytes ?? 0) / 1024) }} KB</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section id="conversation" class="scroll-mt-32 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.conversation') }}</h2>
                    <div class="mt-4 space-y-4">
                        @forelse ($concern->replies as $reply)
                            <div @class([
                                'rounded-lg border p-4',
                                'border-amber-300 bg-amber-50' => $reply->is_internal_note,
                                'border-slate-200 bg-slate-50' => ! $reply->is_internal_note,
                                'reply-enter' => $newReplyId === $reply->id,
                            ])>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $reply->authorLabelFor($currentUser, $concern) }}
                                        </p>
                                        @if ($reply->is_internal_note)
                                            <span class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-900">
                                                🔒 {{ __('labels.internal_note') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $reply->created_at?->format('M d, Y H:i') }}</p>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $reply->body }}</p>

                                @if ($reply->attachments->isNotEmpty())
                                    <ul class="mt-3 space-y-1 border-t border-slate-200 pt-3 text-xs">
                                        @foreach ($reply->attachments as $attachment)
                                            <li>
                                                <a href="{{ route('attachments.download', $attachment) }}" class="font-medium text-spruce-700 hover:underline">
                                                    {{ $attachment->original_name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('messages.no_results') }}</p>
                        @endforelse
                    </div>
                </section>

                @if ($concern->status === \App\Enums\ConcernStatus::Closed)
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600 shadow-sm">
                        {{ __('messages.closed_read_only') }}
                    </section>
                @else
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.reply') }}</h2>
                        <form method="POST" action="{{ route('concerns.reply', $concern) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="reply-body" class="block text-sm font-medium text-slate-700">{{ __('labels.reply') }}</label>
                                <textarea id="reply-body" name="body" rows="4" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">{{ old('body') }}</textarea>
                            </div>
                            <div>
                                <label for="reply-attachments" class="block text-sm font-medium text-slate-700">{{ __('labels.attachments') }}</label>
                                <input id="reply-attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt" class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-spruce-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-spruce-700 hover:file:bg-spruce-100">
                            </div>
                            @if ($isStaff)
                                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <input id="is_internal_note" name="is_internal_note" type="checkbox" value="1" @checked(old('is_internal_note')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                    <label for="is_internal_note" class="text-sm font-medium text-amber-900">{{ __('labels.internal_note') }}</label>
                                </div>
                            @endif
                            <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                                {{ __('labels.reply') }}
                            </button>
                        </form>
                    </section>
                @endif

                @if ($concern->rating)
                    <section class="rounded-xl border border-sage-200 bg-sage-50 p-5 shadow-sm">
                        <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.rating') }}</h2>
                        <p class="mt-2 text-lg tracking-widest text-amber-500" aria-label="{{ $concern->rating->score }} / 5">
                            @for ($star = 1; $star <= 5; $star++)
                                {{ $star <= $concern->rating->score ? '★' : '☆' }}
                            @endfor
                        </p>
                        @if ($concern->rating->comment)
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $concern->rating->comment }}</p>
                        @endif
                    </section>
                @elseif ($currentUser->id === $concern->user_id && $concern->status === \App\Enums\ConcernStatus::Resolved)
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.rate') }}</h2>
                        <form method="POST" action="{{ route('concerns.rate', $concern) }}" class="mt-4 space-y-4">
                            @csrf
                            <fieldset>
                                <legend class="text-sm font-medium text-slate-700">{{ __('labels.score') }}</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @for ($score = 1; $score <= 5; $score++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="score" value="{{ $score }}" required class="peer sr-only" aria-label="{{ $score }} / 5">
                                            <span class="block rounded-lg border border-slate-200 px-3 py-2 text-lg text-amber-400 peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-focus-visible:ring-2 peer-focus-visible:ring-spruce-500">★</span>
                                        </label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div>
                                <label for="rating-comment" class="block text-sm font-medium text-slate-700">{{ __('labels.comment') }}</label>
                                <textarea id="rating-comment" name="comment" rows="3" maxlength="300" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">{{ old('comment') }}</textarea>
                            </div>
                            <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                                {{ __('labels.submit') }}
                            </button>
                        </form>
                    </section>
                @endif

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.audit_trail') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('labels.activity_timeline') }} &middot; {{ __('labels.activity_timeline_help') }}</p>

                    <ol class="mt-5 space-y-5 border-l border-spruce-100 pl-6">
                        @forelse ($auditLogs as $log)
                            <li class="relative">
                                <span class="absolute -left-[1.9rem] top-1.5 h-2.5 w-2.5 rounded-full bg-spruce-500 ring-4 ring-sage-100" aria-hidden="true"></span>
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ __('audit_actions.'.$log->action) }}</p>
                                    <time datetime="{{ $log->created_at?->toIso8601String() }}" class="text-xs text-slate-500">
                                        {{ $log->created_at?->format('M d, Y H:i') }}
                                    </time>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? __('labels.unknown') }}</p>
                                @if ($log->old_value || $log->new_value || $log->reason)
                                    <div class="mt-2 space-y-1 rounded-lg border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600">
                                        @if ($log->old_value)
                                            <p><span class="font-medium text-slate-500">{{ __('labels.old_value') }}:</span> @include('partials.audit_value', ['value' => $log->old_value])</p>
                                        @endif
                                        @if ($log->new_value)
                                            <p><span class="font-medium text-slate-500">{{ __('labels.new_value') }}:</span> @include('partials.audit_value', ['value' => $log->new_value])</p>
                                        @endif
                                        @if ($log->reason)
                                            <p><span class="font-medium text-slate-500">{{ __('labels.reason') }}:</span> {{ $log->reason }}</p>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li>@include('partials.empty_state')</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">
                    <p class="font-semibold text-slate-900">{{ __('labels.status') }}</p>
                    <div class="mt-2">
                        @include('partials.status_pill', ['animateStatus' => session('status_change.concern_id') === $concern->id])
                    </div>
                    <p class="mt-4 font-semibold text-slate-900">{{ __('labels.priority') }}</p>
                    <p class="mt-1">{{ $concern->priority->label() }}</p>

                    @if ($isStaff && $nextStatus !== null)
                        <form method="POST"
                              action="{{ route('concerns.status.update', $concern) }}"
                              class="mt-5 border-t border-slate-100 pt-5"
                              @if ($nextStatus === \App\Enums\ConcernStatus::Closed)
                                  data-confirm
                                  data-confirm-title="{{ __('labels.confirm_close_title') }}"
                                  data-confirm-body="{{ __('labels.confirm_close_body', ['ticket' => $concern->ticket_no]) }}"
                              @endif>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="w-full rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                                {{ __('labels.mark_as', ['status' => $nextStatus->label()]) }}
                            </button>
                        </form>
                    @endif

                    @if ($concern->status === \App\Enums\ConcernStatus::Closed)
                        <p class="mt-4 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-700">
                            {{ __('messages.closed_read_only') }}
                        </p>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
