<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Models\User;
use App\Services\LastVisitedConcernService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConcernShowController extends Controller
{
    /** @var array<int, string> */
    private const STUDENT_VISIBLE_ACTIONS = [
        'created',
        'status_changed',
        'reply_added',
        'attachment_added',
        'rated',
    ];

    public function show(Concern $concern, LastVisitedConcernService $lastVisited): View
    {
        /** @var User $user */
        $user = auth()->user();

        Gate::authorize('view', $concern);

        $lastVisited->remember($concern);

        $concern->load([
            'category',
            'author',
            'recipient',
            'rating',
            'attachments' => $user->isStudent()
                ? fn ($query) => $query->where(function ($query): void {
                    $query->whereNull('reply_id')
                        ->orWhereHas('reply', fn ($replyQuery) => $replyQuery->where('is_internal_note', false));
                })
                : fn ($query) => $query,
        ]);

        $repliesQuery = $concern->replies()->with(['user', 'attachments']);

        if ($user->isStudent()) {
            $repliesQuery->where('is_internal_note', false);
        }

        $concern->setRelation('replies', $repliesQuery->oldest()->get());

        $auditLogsQuery = $concern->auditLogs()->with('user')->latest();

        if ($user->isStudent()) {
            $auditLogsQuery->whereIn('action', self::STUDENT_VISIBLE_ACTIONS);
        }

        $auditLogs = $auditLogsQuery->limit(10)->get();

        return view('concerns.show', compact('concern', 'auditLogs'));
    }
}
