<?php

namespace App\Http\Controllers;

use App\Enums\ConcernStatus;
use App\Http\Requests\StoreReplyRequest;
use App\Models\Concern;
use App\Models\User;
use App\Services\ConcernService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReplyController extends Controller
{
    public function store(StoreReplyRequest $request, Concern $concern, ConcernService $concerns): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $isStaff = $user->isTeacher() || $user->isAdmin();
        $reply = null;
        $advancedFrom = null;

        DB::transaction(function () use ($request, $concern, $user, $concerns, $isStaff, &$reply, &$advancedFrom): void {
            $lockedConcern = Concern::query()
                ->whereKey($concern->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('participate', $lockedConcern);

            $isInternalNote = $isStaff && $request->boolean('is_internal_note');
            $reply = $concerns->addReply(
                $lockedConcern,
                $user,
                $request->validated('body'),
                $isInternalNote,
            );

            $files = array_values(array_filter($request->file('attachments', [])));

            if ($files !== []) {
                $concerns->addAttachments($lockedConcern, $user, $files, $reply);
            }

            if ($isStaff && ! $isInternalNote && $lockedConcern->status === ConcernStatus::Pending) {
                $advancedFrom = $lockedConcern->status;

                $concerns->updateStatus(
                    $lockedConcern,
                    ConcernStatus::UnderReview,
                    $user,
                    __('messages.auto_advanced_after_reply'),
                );
            }
        });

        $response = back()
            ->with('success', __('messages.reply_added'))
            ->with('reply_posted', $reply?->id);

        if ($advancedFrom !== null) {
            $response->with('status_change', [
                'concern_id' => $concern->id,
                'from' => $advancedFrom->value,
                'to' => ConcernStatus::UnderReview->value,
            ]);
        }

        return $response;
    }
}
