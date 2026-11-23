<?php

namespace App\Http\Controllers;

use App\Enums\ConcernStatus;
use App\Http\Requests\StoreConcernRequest;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use App\Services\ConcernService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConcernController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isTeacher()) {
            return redirect()->route('teacher.assigned.index');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.reports.index');
        }

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ConcernStatus::class)],
            'category' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $concerns = Concern::visibleTo($user)
            ->withFilters($filters)
            ->with(['category', 'recipient'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::active()->orderBy('name')->get();

        return view('concerns.index', compact('concerns', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $teachers = User::active()->withRole('teacher')->orderBy('name')->get();
        $templates = __('templates');

        return view('concerns.create', compact('categories', 'teachers', 'templates'));
    }

    public function store(StoreConcernRequest $request, ConcernService $concerns): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $concern = $concerns->create($request->validated(), $user);
        $files = array_values(array_filter($request->file('attachments', [])));

        if ($files !== []) {
            $concerns->addAttachments($concern, $user, $files);
        }

        return redirect()->route('concerns.show', $concern)
            ->with('success', __('messages.concern_created', ['ticket' => $concern->ticket_no]));
    }
}
