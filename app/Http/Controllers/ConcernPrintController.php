<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Services\TicketQrCodeService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConcernPrintController extends Controller
{
    public function show(Concern $concern, TicketQrCodeService $qrCodes): View
    {
        Gate::authorize('view', $concern);

        $concern->load(['category', 'author', 'recipient']);

        return view('concerns.print', [
            'concern' => $concern,
            'qrSvg' => $qrCodes->forConcern($concern),
            'ticketUrl' => route('concerns.show', $concern),
        ]);
    }
}
