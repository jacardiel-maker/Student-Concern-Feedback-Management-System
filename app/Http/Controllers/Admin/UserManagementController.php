<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer'],
            'approval' => ['nullable', 'string', 'in:pending'],
        ]);

        $search = $filters['search'] ?? null;
        $pendingOnly = ($filters['approval'] ?? null) === 'pending';

        $users = User::with('role')
            ->when($search, function ($query, string $search): void {
                $term = '%'.$search.'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('student_number', 'like', $term);
                });
            })
            ->when($filters['role'] ?? null, fn ($query, int $role) => $query->where('role_id', $role))
            ->when($pendingOnly, fn ($query) => $query->awaitingApproval())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();
        $pendingApprovals = User::awaitingApproval()->count();

        return view('admin.users.index', compact('users', 'roles', 'pendingApprovals', 'pendingOnly'));
    }

    public function trashed(): View
    {
        $users = User::onlyTrashed()->with('role')->latest()->paginate(15);

        return view('admin.users.trashed', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $role = Role::find($request->validated('role_id'));
        $isStudent = $role?->name === 'student';

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role_id' => $request->validated('role_id'),
            'student_number' => $isStudent ? $request->validated('student_number') : null,
            'department' => $request->validated('department'),
            'password' => $request->validated('password'),
            'is_active' => $request->boolean('is_active', true),
            'email_opt_in' => $request->boolean('email_opt_in'),
        ]);

        $role = Role::find($user->role_id)?->name ?? 'user';
        $this->audit->record('user_created', auth()->user(), null, null, $user->email.' ('.$role.')');

        return redirect()->route('admin.users.index')->with('success', __('messages.user_created'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $oldRole = $user->role?->name;
        $oldActive = $user->is_active;

        $attributes = $request->validated();
        unset($attributes['password']);
        $attributes['is_active'] = $request->boolean('is_active');
        $attributes['email_opt_in'] = $request->boolean('email_opt_in');

        $role = Role::find($request->validated('role_id'));
        if ($role?->name !== 'student') {
            $attributes['student_number'] = null;
        }

        if ($request->filled('password')) {
            $attributes['password'] = $request->validated('password');
        }

        $user->update($attributes);
        $user->refresh()->load('role');

        $newRole = $user->role?->name;
        if ($oldRole !== $newRole) {
            $this->audit->record('user_role_changed', auth()->user(), null, $user->email.': '.$oldRole, $user->email.': '.$newRole);
        }

        if ($oldActive !== $user->is_active) {
            $this->audit->record(
                $user->is_active ? 'user_activated' : 'user_deactivated',
                auth()->user(),
                null,
                $oldActive ? 'active' : 'inactive',
                $user->is_active ? 'active' : 'inactive',
            );
        }

        $this->audit->record('user_updated', auth()->user(), null, null, $user->email);

        return redirect()->route('admin.users.index')->with('success', __('messages.user_updated'));
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->isStudent() && $user->isAwaitingApproval(), 404);

        $user->forceFill([
            'approval_status' => StudentApprovalStatus::Approved,
            'is_active' => true,
        ])->save();

        $this->audit->record('user_approved', auth()->user(), null, 'pending', 'approved', $user->email);

        return back()->with('success', __('messages.user_approved'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, __('messages.cannot_deactivate_self'));

        $user->update(['is_active' => false]);
        $user->delete();

        $this->audit->record('user_deleted', auth()->user(), null, 'active', 'soft-deleted', $user->email);

        return back()->with('success', __('messages.user_deleted'));
    }

    public function restore(int $user): RedirectResponse
    {
        $trashedUser = User::onlyTrashed()->findOrFail($user);

        $trashedUser->restore();
        $trashedUser->update(['is_active' => true]);

        $this->audit->record('user_restored', auth()->user(), null, 'soft-deleted', 'active', $trashedUser->email);

        return redirect()->route('admin.users.index')->with('success', __('messages.user_restored'));
    }
}
