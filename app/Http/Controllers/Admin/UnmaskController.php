<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConcernPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnmaskRequest;
use App\Models\Concern;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnmaskController extends Controller
{
    public function index(): View
    {
        $concerns = Concern::where('is_anonymous', true)
            ->with(['category', 'recipient'])
            ->latest()
            ->paginate(15);

        return view('admin.unmask.index', compact('concerns'));
    }

    public function show(Request $request, Concern $concern): View
    {
        $this->authorizeSafetyUnmask($concern);

        $logs = $concern->auditLogs()->ofAction('identity_unmasked')->with('user')->latest()->get();
        $revealed = $this->revealedAuthorFromSession($request, $concern);

        return view('admin.unmask.show', compact('concern', 'logs', 'revealed'));
    }

    public function store(UnmaskRequest $request, Concern $concern, AuditService $audit): View
    {
        $this->authorizeSafetyUnmask($concern);

        $author = $concern->author;
        $revealedName = $author?->name ?? __('labels.unknown');

        $audit->record(
            'identity_unmasked',
            $request->user(),
            $concern,
            'anonymous',
            $revealedName,
            $request->validated('reason'),
        );

        $request->session()->put('unmasked_concern_'.$concern->id, $author?->id);
        session()->flash('success', __('messages.unmasked'));

        $logs = $concern->auditLogs()->ofAction('identity_unmasked')->with('user')->latest()->get();
        $revealed = $author;

        return view('admin.unmask.show', compact('concern', 'logs', 'revealed'));
    }

    private function authorizeSafetyUnmask(Concern $concern): void
    {
        abort_unless($concern->isAnonymous(), 404, __('messages.not_found'));

        $concern->loadMissing('category');
        $isSafetyCase = $concern->category?->is_urgent === true
            || $concern->priority === ConcernPriority::Urgent;

        abort_unless($isSafetyCase, 403, __('messages.forbidden'));
    }

    private function revealedAuthorFromSession(Request $request, Concern $concern): ?User
    {
        $userId = $request->session()->get('unmasked_concern_'.$concern->id);

        if (! is_numeric($userId)) {
            return null;
        }

        return $concern->author()->whereKey($userId)->first();
    }
}
