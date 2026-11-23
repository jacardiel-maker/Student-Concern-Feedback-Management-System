<?php

namespace App\Http\Controllers;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Enums\StudentApprovalStatus;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isStudent()) {
            return $this->studentDashboard($user);
        }

        if ($user->isTeacher()) {
            return $this->teacherDashboard($user);
        }

        return $this->adminDashboard();
    }

    private function studentDashboard(User $user): View
    {
        $concerns = Concern::whereBelongsTo($user, 'author');

        $stats = [
            'total' => (clone $concerns)->count(),
            'pending' => (clone $concerns)->where('status', ConcernStatus::Pending)->count(),
            'in_progress' => (clone $concerns)->where('status', ConcernStatus::InProgress)->count(),
            'resolved' => (clone $concerns)->where('status', ConcernStatus::Resolved)->count(),
        ];

        $recentConcerns = (clone $concerns)
            ->with(['category', 'recipient'])
            ->latest()
            ->take(5)
            ->get();

        return view('student.dashboard', compact('stats', 'recentConcerns'));
    }

    private function teacherDashboard(User $user): View
    {
        $assigned = Concern::query()->where('recipient_id', $user->id);
        $openStatuses = [ConcernStatus::Pending, ConcernStatus::UnderReview, ConcernStatus::InProgress];

        $stats = [
            'assigned' => (clone $assigned)->count(),
            'pending' => (clone $assigned)->where('status', ConcernStatus::Pending)->count(),
            'open' => (clone $assigned)->whereIn('status', $openStatuses)->count(),
            'resolved' => (clone $assigned)->where('status', ConcernStatus::Resolved)->count(),
        ];

        $urgentQueue = (clone $assigned)
            ->whereIn('status', $openStatuses)
            ->where(function ($query): void {
                $query->where('priority', ConcernPriority::Urgent)
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_urgent', true));
            })
            ->with(['category', 'author'])
            ->oldest()
            ->take(10)
            ->get();

        $awaitingResponse = (clone $assigned)
            ->whereIn('status', [ConcernStatus::Pending, ConcernStatus::UnderReview])
            ->whereDoesntHave('replies', fn ($query) => $query->where('user_id', $user->id))
            ->with(['category', 'author'])
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('teacher.dashboard', compact('stats', 'urgentQueue', 'awaitingResponse'));
    }

    private function adminDashboard(): View
    {
        $stats = [
            'total' => Concern::count(),
            'pending' => Concern::where('status', ConcernStatus::Pending)->count(),
            'urgent' => Concern::where('priority', ConcernPriority::Urgent)
                ->where('status', '!=', ConcernStatus::Closed)
                ->count(),
            'resolved' => Concern::where('status', ConcernStatus::Resolved)->count(),
            'unassigned' => Concern::unassigned()->count(),
            'pending_approvals' => User::where('approval_status', StudentApprovalStatus::Pending)->count(),
        ];

        $statusCounts = Concern::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $categoryCounts = Category::withCount('concerns')
            ->pluck('concerns_count', 'name')
            ->all();

        $monthlyTrend = [];

        foreach (Concern::query()->whereNotNull('created_at')->select('created_at')->cursor() as $concern) {
            $month = $concern->created_at->format('Y-m');
            $monthlyTrend[$month] = ($monthlyTrend[$month] ?? 0) + 1;
        }

        ksort($monthlyTrend);

        $urgentConcerns = Concern::with(['category', 'author', 'recipient'])
            ->where('status', '!=', ConcernStatus::Closed)
            ->where(function ($query): void {
                $query->where('priority', ConcernPriority::Urgent)
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_urgent', true));
            })
            ->latest()
            ->take(8)
            ->get();

        $recentConcerns = Concern::with(['category', 'author', 'recipient'])
            ->latest()
            ->take(5)
            ->get();

        $categoryStatusMatrix = $this->categoryStatusMatrix();

        return view('admin.dashboard', compact(
            'stats',
            'statusCounts',
            'categoryCounts',
            'monthlyTrend',
            'categoryStatusMatrix',
            'urgentConcerns',
            'recentConcerns',
        ));
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function categoryStatusMatrix(): array
    {
        $statusValues = array_map(
            fn (ConcernStatus $status): string => $status->value,
            ConcernStatus::cases(),
        );

        $matrix = [];

        foreach (Category::orderBy('name')->pluck('name') as $categoryName) {
            $matrix[$categoryName] = array_fill_keys($statusValues, 0);
        }

        $counts = Concern::query()
            ->join('categories', 'categories.id', '=', 'concerns.category_id')
            ->selectRaw('categories.name as category_name, concerns.status as status, COUNT(*) as total')
            ->groupBy('categories.name', 'concerns.status')
            ->get();

        foreach ($counts as $count) {
            $status = $count->status instanceof ConcernStatus ? $count->status->value : $count->status;

            if (isset($matrix[$count->category_name]) && isset($matrix[$count->category_name][$status])) {
                $matrix[$count->category_name][$status] = (int) $count->total;
            }
        }

        return $matrix;
    }
}
