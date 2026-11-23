@extends('layouts.app')

@section('pageTitle', __('labels.dashboard'))

@section('content')
    @php
        $statusMaximum = ! empty($statusCounts) ? max($statusCounts) : 1;
        $categoryMaximum = ! empty($categoryCounts) ? max($categoryCounts) : 1;
        $trendMaximum = ! empty($monthlyTrend) ? max($monthlyTrend) : 1;
        $heatmapMaximum = 1;

        foreach ($categoryStatusMatrix as $matrixRow) {
            $heatmapMaximum = max($heatmapMaximum, ! empty($matrixRow) ? max($matrixRow) : 0);
        }
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-spruce-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ __('labels.admin_portal') }}</p>
                <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.overview') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ __('labels.urgent_open_concerns') }}</p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                {{ __('labels.reports') }}
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @include('partials.stat_card', ['label' => __('labels.total'), 'value' => $stats['total'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.pending_count'), 'value' => $stats['pending'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.open_count'), 'value' => ($stats['total'] ?? 0) - ($stats['resolved'] ?? 0)])
            @include('partials.stat_card', ['label' => __('labels.resolved_count'), 'value' => $stats['resolved'] ?? 0])
            @include('partials.stat_card', ['label' => __('labels.unassigned'), 'value' => $stats['unassigned'] ?? 0])
            <a href="{{ route('admin.users.index', ['approval' => 'pending']) }}" class="border-l-4 border-clay-500 bg-clay-50/60 px-5 py-4 transition hover:bg-clay-50">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-clay-700">{{ __('labels.pending_approvals') }}</p>
                <p class="mt-2 font-serif text-3xl font-semibold text-clay-900">{{ $stats['pending_approvals'] ?? 0 }}</p>
            </a>
        </div>

        <nav aria-label="{{ __('labels.quick_links') }}" class="flex flex-wrap items-center gap-x-6 gap-y-2 border-y border-spruce-100 bg-white/50 px-5 py-3">
            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-sage-600">{{ __('labels.quick_links') }}</span>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">{{ __('labels.users') }}</a>
            <a href="{{ route('admin.assignments.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">{{ __('labels.assignments') }}</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">{{ __('labels.audit_logs') }}</a>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-spruce-700 hover:underline">{{ __('labels.reports') }}</a>
            <a href="{{ route('admin.backup.download') }}" title="{{ __('labels.backup_zip_help') }}" class="text-sm font-semibold text-spruce-700 hover:underline">{{ __('labels.backup_zip') }}</a>
        </nav>

        <section class="border-l-4 border-clay-500 bg-clay-50/60">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <h2 class="font-serif text-lg font-semibold text-clay-900">{{ __('labels.urgent_open_concerns') }}</h2>
                <span class="rounded-full bg-clay-100 px-2.5 py-1 text-xs font-semibold text-clay-800">
                    {{ $urgentConcerns->count() }}
                </span>
            </div>

            <div class="border-t border-clay-200 bg-white/70">
                @forelse ($urgentConcerns as $concern)
                    <article class="grid gap-3 border-b border-clay-100 px-5 py-4 last:border-b-0 lg:grid-cols-[11rem_1fr_10rem_10rem_auto] lg:items-center">
                        @include('partials.ticket_badges')

                        <div class="min-w-0">
                            <p class="font-serif text-base font-semibold text-slate-900">{{ $concern->subject }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $concern->category?->name ?? __('labels.unknown') }}</p>
                        </div>

                        <p class="text-sm text-slate-600">{{ $concern->recipient?->name ?? __('labels.unassigned') }}</p>
                        <p class="text-xs text-slate-500">{{ $concern->created_at?->format('M d, Y H:i') }}</p>

                        <a href="{{ route('concerns.show', $concern) }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                            {{ __('labels.details') }}
                        </a>
                    </article>
                @empty
                    <div class="px-5 py-6">@include('partials.empty_state')</div>
                @endforelse
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-3">
            <section class="border-t-4 border-spruce-400 bg-sage-50/80 px-5 py-5">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.by_status') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($statusCounts as $status => $count)
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-600">
                                <span>{{ __('statuses.' . $status) }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-white/70">
                                <div class="h-full rounded-full bg-spruce-500" style="width: {{ $statusMaximum > 0 ? (int) round(($count / $statusMaximum) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('messages.no_results') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="border-t-4 border-sage-400 bg-white/70 px-5 py-5">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.by_category') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($categoryCounts as $categoryName => $count)
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-600">
                                <span class="truncate">{{ $categoryName }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-sage-100">
                                <div class="h-full rounded-full bg-sage-500" style="width: {{ $categoryMaximum > 0 ? (int) round(($count / $categoryMaximum) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('messages.no_results') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="border-t-4 border-amber-brand bg-spruce-50/80 px-5 py-5">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.monthly_trend') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse (collect($monthlyTrend)->sortKeys() as $month => $count)
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-600">
                                <span>{{ $month }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-white/70">
                                <div class="h-full rounded-full bg-amber-brand" style="width: {{ $trendMaximum > 0 ? (int) round(($count / $trendMaximum) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('messages.no_results') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.category_status_heatmap') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('labels.category_status_heatmap_help') }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] border-separate border-spacing-1 text-left text-xs">
                    <thead>
                        <tr class="text-slate-500">
                            <th class="px-3 py-2 font-medium">{{ __('labels.category') }}</th>
                            @foreach (\App\Enums\ConcernStatus::cases() as $status)
                                <th class="px-3 py-2 text-center font-medium">{{ $status->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoryStatusMatrix as $categoryName => $counts)
                            <tr>
                                <th class="whitespace-nowrap px-3 py-2 font-medium text-slate-700">{{ $categoryName }}</th>
                                @foreach ($counts as $status => $count)
                                    @php($intensity = $heatmapMaximum > 0 ? $count / $heatmapMaximum : 0)
                                    <td class="p-0">
                                        <div class="rounded-lg px-3 py-2 text-center font-semibold"
                                             style="background-color: rgb(20 85 78 / {{ number_format($intensity * 0.85, 2) }}); color: {{ $intensity > 0.45 ? '#ffffff' : '#0b3833' }};"
                                             title="{{ $categoryName }} &mdash; {{ __('statuses.'.$status) }}: {{ $count }}">
                                            {{ $count }}
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count(\App\Enums\ConcernStatus::cases()) + 1 }}">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-label="{{ __('labels.recent_concerns') }}" class="border-y border-spruce-100 bg-white/60">
            <div class="flex items-center justify-between gap-3 border-b border-spruce-100 px-5 py-4">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.recent_concerns') }}</h2>
            </div>

            @forelse ($recentConcerns as $concern)
                <article class="grid gap-3 border-b border-spruce-100 px-5 py-4 last:border-b-0 lg:grid-cols-[11rem_1fr_10rem_10rem_auto] lg:items-center">
                    @include('partials.ticket_badges')

                    <div class="min-w-0">
                        <p class="font-serif text-base font-semibold text-slate-900">{{ $concern->subject }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $concern->category?->name ?? __('labels.unknown') }}</p>
                    </div>

                    <p class="text-sm text-slate-600">{{ $concern->recipient?->name ?? __('labels.unassigned') }}</p>
                    <p class="text-xs text-slate-500">{{ $concern->created_at?->format('M d, Y H:i') }}</p>

                    <a href="{{ route('concerns.show', $concern) }}" class="text-sm font-semibold text-spruce-700 hover:underline">
                        {{ __('labels.details') }}
                    </a>
                </article>
            @empty
                <div class="px-5 py-6">@include('partials.empty_state')</div>
            @endforelse
        </section>
    </div>
@endsection
