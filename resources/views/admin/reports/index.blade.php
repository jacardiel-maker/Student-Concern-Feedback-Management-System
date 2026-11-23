@extends('layouts.app')

@section('pageTitle', __('labels.reports'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.reports') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.reports_help') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.backup.download') }}" title="{{ __('labels.backup_zip_help') }}" class="rounded-lg border border-spruce-200 bg-sage-50 px-4 py-2 text-sm font-semibold text-spruce-700 hover:bg-sage-100">
                    {{ __('labels.download_zip_backup') }}
                </a>
                <a href="{{ route('admin.reports.csv', request()->query()) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('labels.export_csv') }}
                </a>
                <a href="{{ route('admin.reports.print', request()->query()) }}" target="_blank" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('labels.export_pdf') }}
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
            <div>
                <label for="filter-from" class="block text-xs font-medium text-slate-600">{{ __('labels.from') }}</label>
                <input id="filter-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div>
                <label for="filter-to" class="block text-xs font-medium text-slate-600">{{ __('labels.to') }}</label>
                <input id="filter-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>
            <div>
                <label for="filter-status" class="block text-xs font-medium text-slate-600">{{ __('labels.status') }}</label>
                <select id="filter-status" name="status" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach (\App\Enums\ConcernStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="block text-xs font-medium text-slate-600">{{ __('labels.category') }}</label>
                <select id="filter-category" name="category" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected((string) ($filters['category'] ?? '') === (string) $categoryOption->id)>{{ $categoryOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-4 flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.filter') }}</button>
                <a href="{{ route('admin.reports.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('labels.all') }}</a>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ __('labels.total_concerns') }}</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $total }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $anonymous }} {{ __('labels.anonymous') }}</p>
            </section>
            @include('partials.stat_card', ['label' => __('labels.avg_resolution_hours'), 'value' => number_format($avgResolutionHours, 2)])
            @include('partials.stat_card', ['label' => __('labels.average_rating'), 'value' => number_format($avgRating, 2)])
            @include('partials.stat_card', ['label' => __('labels.open_unresolved'), 'value' => $open])
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.by_category') }}</h2>
                <div class="mt-4 h-72"><canvas id="categoryChart"></canvas></div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.by_status') }}</h2>
                <div class="mt-4 h-72"><canvas id="statusChart"></canvas></div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.by_priority') }}</h2>
                <div class="mt-4 h-72"><canvas id="priorityChart"></canvas></div>
            </section>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.monthly_submissions') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('labels.monthly_trend') }}</p>
            <div class="mt-4 h-80"><canvas id="monthlyChart"></canvas></div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.teacher_satisfaction') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('labels.teacher_satisfaction_help') }}</p>
            <div class="mt-4 h-72"><canvas id="satisfactionChart"></canvas></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.latest_activity') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('labels.date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.action') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.users') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('labels.ticket_no') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($latestAuditLogs as $log)
                            <tr>
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
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('partials.empty_state')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @php
        $chartPayload = [
            'category' => ['labels' => array_keys($categoryCounts), 'data' => array_values($categoryCounts), 'color' => '#2f6f63'],
            'status' => ['labels' => array_map(fn ($s) => __('statuses.'.$s), array_keys($statusCounts)), 'data' => array_values($statusCounts), 'color' => '#c8811a'],
            'priority' => ['labels' => array_map(fn ($p) => __('labels.priority_'.$p), array_keys($priorityCounts)), 'data' => array_values($priorityCounts), 'color' => '#b25a45'],
            'monthly' => ['labels' => array_map(fn ($month) => \Carbon\Carbon::parse($month)->format('M Y'), array_keys($monthlyTrend)), 'data' => array_values($monthlyTrend), 'color' => '#14554e'],
            'satisfaction' => ['labels' => array_keys($teacherRatings), 'data' => array_values($teacherRatings), 'color' => '#2f6f63'],
        ];
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const reportCharts = @json($chartPayload);
        const makeBar = (id, cfg, max = undefined) => {
            const el = document.getElementById(id);
            if (!el || !window.Chart) return;
            new Chart(el, {
                type: 'bar',
                data: { labels: cfg.labels, datasets: [{ data: cfg.data, backgroundColor: cfg.color, borderRadius: 6 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, max, ticks: { precision: max === undefined ? 0 : 1 } } }
                }
            });
        };
        const makeLine = (id, cfg) => {
            const el = document.getElementById(id);
            if (!el || !window.Chart) return;
            new Chart(el, {
                type: 'line',
                data: {
                    labels: cfg.labels,
                    datasets: [{
                        data: cfg.data,
                        borderColor: cfg.color,
                        backgroundColor: cfg.color + '22',
                        pointBackgroundColor: cfg.color,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.35,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        };
        makeBar('categoryChart', reportCharts.category);
        makeBar('statusChart', reportCharts.status);
        makeBar('priorityChart', reportCharts.priority);
        makeLine('monthlyChart', reportCharts.monthly);
        makeBar('satisfactionChart', reportCharts.satisfaction, 5);
    </script>
@endsection
