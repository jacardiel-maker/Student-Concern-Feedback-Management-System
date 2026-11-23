@extends('layouts.app')

@section('pageTitle', __('labels.notifications'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.notifications') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.notification_center_help') }}</p>
            </div>
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('labels.mark_all_as_read') }}
                </button>
            </form>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php
                        $icon = match ($notification->type) {
                            'urgent_alert' => '!',
                            'new_reply' => '💬',
                            'rated' => '★',
                            'assigned' => '→',
                            'status_change' => '●',
                            default => '•',
                        };
                    @endphp
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between {{ $notification->is_read ? '' : 'bg-spruce-50/40' }}">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-700" aria-hidden="true">{{ $icon }}</span>
                                <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                                @unless ($notification->is_read)
                                    <span class="rounded-full bg-spruce-600 px-2 py-0.5 text-xs font-medium text-white">{{ __('labels.new') }}</span>
                                @endunless
                                @if ($notification->concern)
                                    <span class="font-mono text-xs text-slate-500">{{ $notification->concern->ticket_no }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-600">{{ $notification->body }}</p>
                            <p class="text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="shrink-0">
                            @if ($notification->concern)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-md bg-spruce-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-spruce-700">
                                        {{ __('labels.view') }}
                                    </button>
                                </form>
                            @elseif (! $notification->is_read)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-medium text-spruce-700 hover:underline">
                                        {{ __('labels.mark_as_read') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    @include('partials.empty_state', ['message' => __('labels.no_notifications')])
                @endforelse
            </div>
        </section>
    </div>
@endsection
