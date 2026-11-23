<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConcernStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignConcernRequest;
use App\Models\Concern;
use App\Models\User;
use App\Services\ConcernService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ConcernStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['all', 'open', 'unassigned'])],
        ]);

        $scope = $filters['scope'] ?? 'open';

        $concerns = Concern::with(['category', 'author', 'recipient'])
            ->withFilters($filters)
            ->when($scope === 'open', fn ($query) => $query->where('status', '!=', ConcernStatus::Closed))
            ->when($scope === 'unassigned', fn ($query) => $query->where('status', '!=', ConcernStatus::Closed)->unassigned())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $teachers = User::active()->withRole('teacher')->orderBy('name')->get();
        $suggestions = $this->assignmentSuggestions();

        return view('admin.assignments.index', compact('concerns', 'teachers', 'suggestions'));
    }

    /**
     * @return Collection<int|string, Concern>
     */
    private function assignmentSuggestions(): Collection
    {
        return Concern::query()
            ->selectRaw('category_id, recipient_id, COUNT(*) as handled_count')
            ->whereNotNull('recipient_id')
            ->whereHas('recipient', fn ($query) => $query->active()->withRole('teacher'))
            ->groupBy('category_id', 'recipient_id')
            ->orderByDesc('handled_count')
            ->with(['category:id,name', 'recipient:id,name'])
            ->get()
            ->groupBy('category_id')
            ->map(fn ($group) => $group->first());
    }

    public function store(
        AssignConcernRequest $request,
        Concern $concern,
        ConcernService $concerns,
    ): RedirectResponse {
        $teacher = User::findOrFail($request->validated('recipient_id'));

        $concerns->assign($concern, $teacher, auth()->user());

        return back()->with('success', __('messages.assigned'));
    }

    public function destroy(Concern $concern, ConcernService $concerns): RedirectResponse
    {
        $concerns->unassign($concern, auth()->user());

        return back()->with('success', __('messages.unassigned'));
    }
}
