<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssignedConcernController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ConcernStatus::class)],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'priority' => ['nullable', Rule::enum(ConcernPriority::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $concerns = Concern::visibleTo($user)
            ->withFilters($filters)
            ->with(['category', 'author', 'recipient'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::active()->orderBy('name')->get();

        return view('teacher.assigned.index', compact('concerns', 'categories'));
    }
}
