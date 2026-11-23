<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConcernSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:150'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $validated['subject']).'%';

        $suggestions = Concern::query()
            ->visibleTo($user)
            ->where('subject', 'like', $term)
            ->latest()
            ->limit(5)
            ->get(['id', 'ticket_no', 'subject', 'status'])
            ->map(fn (Concern $concern): array => [
                'ticket_no' => $concern->ticket_no,
                'subject' => $concern->subject,
                'status' => $concern->status->label(),
                'url' => route('concerns.show', $concern),
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }
}
