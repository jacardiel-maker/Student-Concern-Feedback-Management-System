<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Concern;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$filters, $concerns] = $this->reportData($request, true);
        $stats = $this->buildStats($filters);

        $latestAuditLogs = AuditLog::with(['user', 'concern'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.reports.index', [
            'filters' => $filters,
            'concerns' => $concerns,
            'categories' => Category::orderBy('name')->get(),
            'latestAuditLogs' => $latestAuditLogs,
            ...$stats,
        ]);
    }

    public function csv(Request $request, AuditService $audit): StreamedResponse
    {
        [$filters, $concerns] = $this->reportData($request, false);
        $viewer = $request->user();

        $audit->record(
            'report_exported',
            $viewer,
            null,
            null,
            'csv',
            $this->filterSummary($filters),
        );

        $callback = function () use ($concerns): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                __('labels.ticket_no'),
                __('labels.subject'),
                __('labels.category'),
                __('labels.status'),
                __('labels.priority'),
                __('labels.anonymous'),
                __('labels.author'),
                __('labels.assigned_to'),
                __('labels.submitted_at'),
                __('labels.resolved_at'),
                __('labels.resolution_hours'),
                __('labels.rating'),
            ]);

            foreach ($concerns as $concern) {
                fputcsv($handle, [
                    $concern->ticket_no,
                    $concern->subject,
                    $concern->category?->name,
                    $concern->status->label(),
                    $concern->priority->label(),
                    $concern->isAnonymous() ? 'yes' : 'no',
                    $concern->isAnonymous() ? 'anonymous' : ($concern->author?->name ?? ''),
                    $concern->recipient?->name,
                    $concern->created_at?->toDateTimeString(),
                    $concern->resolved_at?->toDateTimeString(),
                    $this->resolutionHours($concern),
                    $concern->rating?->score,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'scfms-report-'.now()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request, AuditService $audit): View
    {
        [$filters] = $this->reportData($request, false);
        $stats = $this->buildStats($filters);

        $audit->record(
            'report_exported',
            $request->user(),
            null,
            null,
            'print',
            $this->filterSummary($filters),
        );

        return view('admin.reports.print', [
            'filters' => $filters,
            'generatedBy' => $request->user(),
            'generatedAt' => now(),
            ...$stats,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: mixed}
     */
    private function reportData(Request $request, bool $paginate): array
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ConcernStatus::class)],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $filters['from'] = $filters['from'] ?? now()->startOfYear()->toDateString();
        $filters['to'] = $filters['to'] ?? now()->toDateString();

        $query = Concern::query()
            ->withFilters($filters)
            ->with(['category', 'author', 'recipient', 'rating'])
            ->latest();

        $concerns = $paginate
            ? $query->paginate(15)->withQueryString()
            : $query->lazy();

        return [$filters, $concerns];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildStats(array $filters): array
    {
        $base = Concern::query()->withFilters($filters);

        $total = (clone $base)->count();
        $anonymous = (clone $base)->where('is_anonymous', true)->count();
        $open = (clone $base)->where('status', '!=', ConcernStatus::Closed)->where('status', '!=', ConcernStatus::Resolved)->count();
        $avgRating = round((float) ((clone $base)->whereHas('rating')->withAvg('rating', 'score')->get()->avg('rating_avg_score') ?? 0), 2);

        $avgMinutesExpression = $this->avgResolutionMinutesExpression();

        $resolutionMinutes = (clone $base)
            ->whereNotNull('resolved_at')
            ->selectRaw("AVG({$avgMinutesExpression}) as avg_minutes")
            ->value('avg_minutes');

        $avgResolutionHours = round(((float) ($resolutionMinutes ?? 0)) / 60, 2);

        $statusCounts = $this->statusCounts($filters);
        $categoryCounts = $this->categoryCounts($filters);
        $priorityCounts = $this->priorityCounts($filters);
        $teacherRatings = $this->teacherRatings($filters);
        $monthlyTrend = $this->monthlyTrend($filters);

        return compact(
            'total',
            'anonymous',
            'open',
            'avgRating',
            'avgResolutionHours',
            'statusCounts',
            'categoryCounts',
            'priorityCounts',
            'teacherRatings',
            'monthlyTrend',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function statusCounts(array $filters): array
    {
        $counts = Concern::query()
            ->withFilters($filters)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $result = [];

        foreach (ConcernStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function categoryCounts(array $filters): array
    {
        $counts = Concern::query()
            ->withFilters($filters)
            ->join('categories', 'categories.id', '=', 'concerns.category_id')
            ->selectRaw('categories.name as name, COUNT(*) as total')
            ->groupBy('categories.name')
            ->orderBy('categories.name')
            ->pluck('total', 'name')
            ->all();

        return collect($counts)->map(fn ($count): int => (int) $count)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function priorityCounts(array $filters): array
    {
        $counts = Concern::query()
            ->withFilters($filters)
            ->selectRaw('priority, COUNT(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->all();

        $result = [];

        foreach (ConcernPriority::cases() as $priority) {
            $result[$priority->value] = (int) ($counts[$priority->value] ?? 0);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function teacherRatings(array $filters): array
    {
        $ratings = Concern::query()
            ->withFilters($filters)
            ->whereNotNull('concerns.recipient_id')
            ->join('users', 'users.id', '=', 'concerns.recipient_id')
            ->join('ratings', 'ratings.concern_id', '=', 'concerns.id')
            ->whereNull('users.deleted_at')
            ->selectRaw('users.name as teacher_name, AVG(ratings.score) as average_rating')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('average_rating')
            ->pluck('average_rating', 'teacher_name')
            ->all();

        return collect($ratings)->map(fn ($rating): float => round((float) $rating, 2))->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function monthlyTrend(array $filters): array
    {
        $counts = [];

        foreach (Concern::query()
            ->withFilters($filters)
            ->whereNotNull('created_at')
            ->select('created_at')
            ->cursor() as $concern) {
            $month = $concern->created_at->format('Y-m');
            $counts[$month] = ($counts[$month] ?? 0) + 1;
        }

        $start = Carbon::parse($filters['from'])->startOfMonth();
        $end = Carbon::parse($filters['to'])->startOfMonth();
        $trend = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $key = $month->format('Y-m');
            $trend[$key] = $counts[$key] ?? 0;
        }

        return $trend;
    }

    private function resolutionHours(Concern $concern): ?string
    {
        if ($concern->resolved_at === null || $concern->created_at === null) {
            return null;
        }

        return (string) round($concern->created_at->diffInMinutes($concern->resolved_at) / 60, 2);
    }

    private function avgResolutionMinutesExpression(): string
    {
        $driver = Concern::query()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => '(julianday(resolved_at) - julianday(created_at)) * 1440',
            default => 'TIMESTAMPDIFF(MINUTE, created_at, resolved_at)',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filterSummary(array $filters): string
    {
        $parts = [];

        foreach (['from', 'to', 'status', 'category', 'search'] as $key) {
            $value = $filters[$key] ?? null;
            if ($value !== null && $value !== '') {
                $parts[] = $key.'='.$value;
            }
        }

        return $parts === [] ? 'all' : implode(', ', $parts);
    }
}
