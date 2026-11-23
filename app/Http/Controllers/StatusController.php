<?php

namespace App\Http\Controllers;

use App\Enums\ConcernStatus;
use App\Http\Requests\UpdateConcernStatusRequest;
use App\Models\Concern;
use App\Services\ConcernService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class StatusController extends Controller
{
    public function update(
        UpdateConcernStatusRequest $request,
        Concern $concern,
        ConcernService $concerns,
    ): RedirectResponse {
        Gate::authorize('manage', $concern);

        $previousStatus = $concern->status;
        $nextStatus = ConcernStatus::from($request->validated('status'));

        try {
            $concerns->updateStatus(
                $concern,
                $nextStatus,
                auth()->user(),
                $request->validated('note'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return back()
            ->with('success', __('messages.status_changed'))
            ->with('status_change', [
                'concern_id' => $concern->id,
                'from' => $previousStatus->value,
                'to' => $nextStatus->value,
            ]);
    }
}
