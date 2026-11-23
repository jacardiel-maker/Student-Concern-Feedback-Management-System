<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $categories = Category::withCount('concerns')->orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create([
            'name' => $request->validated('name'),
            'name_fil' => $request->validated('name_fil'),
            'is_urgent' => $request->boolean('is_urgent'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->audit->record('category_created', auth()->user(), null, null, $category->name);

        return redirect()->route('admin.categories.index')->with('success', __('messages.category_created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $oldUrgent = $category->is_urgent;
        $oldActive = $category->is_active;

        $category->update([
            'name' => $request->validated('name'),
            'name_fil' => $request->validated('name_fil'),
            'is_urgent' => $request->boolean('is_urgent'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($oldUrgent !== $category->is_urgent) {
            $this->audit->record(
                'category_urgency_changed',
                auth()->user(),
                null,
                $category->name.': '.($oldUrgent ? 'urgent' : 'normal'),
                $category->name.': '.($category->is_urgent ? 'urgent' : 'normal'),
            );
        }

        if ($oldActive !== $category->is_active) {
            $this->audit->record(
                $category->is_active ? 'category_activated' : 'category_deactivated',
                auth()->user(),
                null,
                $category->name.': '.($oldActive ? 'active' : 'inactive'),
                $category->name.': '.($category->is_active ? 'active' : 'inactive'),
            );
        }

        return redirect()->route('admin.categories.index')->with('success', __('messages.category_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->concerns()->exists()) {
            $category->update(['is_active' => false]);

            return back()->with('success', __('messages.category_deactivated'));
        }

        $category->delete();

        return back()->with('success', __('messages.category_deleted'));
    }
}
