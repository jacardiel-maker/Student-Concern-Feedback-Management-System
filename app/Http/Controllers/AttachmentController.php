<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment): StreamedResponse|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $concern = $attachment->concern;

        abort_unless($concern !== null, 404, __('messages.not_found'));
        Gate::authorize('view', $concern);

        if ($attachment->reply?->is_internal_note && ! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, __('messages.forbidden'));
        }

        if (! Storage::disk('attachments')->exists($attachment->stored_path)) {
            return back()->with('error', __('messages.attachment_missing'));
        }

        return Storage::disk('attachments')->download($attachment->stored_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }
}
