@extends('layouts.app')

@section('pageTitle', __('labels.unmask') . ' ' . $concern->ticket_no)

@section('content')
    <div class="space-y-6">
        <a href="{{ route('admin.unmask.index') }}" class="text-sm font-medium text-spruce-700 hover:underline">{{ __('labels.back') }}</a>

        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5">
            <h2 class="text-base font-semibold text-amber-900">{{ __('labels.privacy_warning') }}</h2>
            <p class="mt-2 text-sm text-amber-900">{{ __('labels.unmask_safety_only') }}</p>
        </div>

        @if (isset($revealed) && $revealed)
            <section class="rounded-xl border border-sage-200 bg-sage-50 p-5 shadow-sm">
                <h2 class="text-base font-semibold text-spruce-800">{{ __('labels.revealed_author') }}</h2>
                <p class="mt-2 text-sm text-spruce-800">
                    {{ $revealed->name }}
                    @if ($revealed->student_number)
                        <span class="block text-xs font-normal text-spruce-700">{{ $revealed->student_number }}</span>
                    @endif
                    <span class="block text-xs font-normal text-spruce-700">{{ $revealed->email }}</span>
                </p>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h1 class="text-xl font-semibold text-slate-900">{{ $concern->subject }}</h1>
                        @include('partials.ticket_badges', ['ticketRoute' => 'admin.unmask.show'])
                    </div>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.category') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->category?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.author') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ __('labels.anonymous') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('labels.submitted_at') }}</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $concern->created_at?->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                    <p class="mt-5 whitespace-pre-line border-t border-slate-100 pt-5 text-sm leading-relaxed text-slate-700">{{ $concern->description }}</p>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.audit_logs') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($logs as $log)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-900">{{ $log->action }}</span>
                                    <span class="text-xs text-slate-500">{{ $log->created_at?->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-600">{{ __('labels.users') }}: {{ $log->user?->name ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ __('labels.reason') }}: {{ $log->reason ?? '—' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('messages.no_results') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div>
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.unmask') }}</h2>
                    <form method="POST" action="{{ route('admin.unmask.store', $concern) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="reason" class="block text-sm font-medium text-slate-700">{{ __('labels.reason') }}</label>
                            <textarea id="reason" name="reason" rows="5" required minlength="5" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">{{ old('reason') }}</textarea>
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('labels.reason_required') }}</p>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                            {{ __('labels.unmask') }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
