<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('labels.reports') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print { .no-print { display: none !important; } body { background: white; } }
    </style>
</head>
<body class="bg-white p-8 text-slate-800">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-start justify-between gap-4 border-b border-slate-300 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ config('app.name') }} — {{ __('labels.reports') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('labels.period') }}: {{ $filters['from'] ?? '' }} {{ __('labels.to') }} {{ $filters['to'] ?? '' }}</p>
                <p class="text-sm text-slate-600">{{ __('labels.generated_by') }}: {{ $generatedBy->name }} | {{ $generatedAt->format('M d, Y H:i') }}</p>
            </div>
            <button onclick="window.print()" class="no-print rounded-lg bg-spruce-600 px-4 py-2 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.save_as_pdf') }}</button>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="rounded-lg border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">{{ __('labels.total_concerns') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $total }}</p>
                <p class="text-xs text-slate-500">{{ $anonymous }} {{ __('labels.anonymous') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">{{ __('labels.avg_resolution_hours') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($avgResolutionHours, 2) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">{{ __('labels.average_rating') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($avgRating, 2) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">{{ __('labels.open_unresolved') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $open }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6">
            @foreach ([__('labels.by_category') => $categoryCounts, __('labels.by_status') => $statusCounts, __('labels.by_priority') => $priorityCounts] as $title => $counts)
                <section class="rounded-lg border border-slate-200">
                    <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">{{ $title }}</h2>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($counts as $name => $count)
                            @php
                                $label = $name;
                                if ($title === __('labels.by_status')) {
                                    $label = __('statuses.'.$name);
                                } elseif ($title === __('labels.by_priority')) {
                                    $label = __('labels.priority_'.$name);
                                }
                            @endphp
                            <tr>
                                <td class="px-4 py-2">{{ $label }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">@include('partials.empty_state')</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>

        <p class="border-t border-slate-200 pt-4 text-xs text-slate-500">{{ __('labels.report_confidentiality_notice') }}</p>
    </div>
    <script>window.addEventListener('load', () => { if (!window.matchMedia('print').matches) { window.print(); } });</script>
</body>
</html>
