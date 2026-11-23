<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Concern;
use App\Services\ConcernService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RatingController extends Controller
{
    public function create(Concern $concern): View
    {
        Gate::authorize('rate', $concern);

        return view('concerns.rate', compact('concern'));
    }

    public function store(
        StoreRatingRequest $request,
        Concern $concern,
        ConcernService $concerns,
    ): RedirectResponse {
        Gate::authorize('rate', $concern);

        $concerns->rate($concern, auth()->user(), $request->validated());

        return redirect()->route('concerns.show', $concern)
            ->with('success', __('messages.rated'));
    }
}
