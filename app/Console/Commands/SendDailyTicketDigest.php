<?php

namespace App\Console\Commands;

use App\Enums\ConcernStatus;
use App\Mail\DailyTicketDigestMail;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-daily-ticket-digest')]
#[Description('Send teachers a daily summary of their open tickets.')]
class SendDailyTicketDigest extends Command
{
    public function handle(): int
    {
        if (! config('services.notifications.email_enabled')) {
            $this->info('Email notifications are disabled; no digests sent.');

            return self::SUCCESS;
        }

        $sent = 0;

        User::active()
            ->withRole('teacher')
            ->where('email_opt_in', true)
            ->each(function (User $teacher) use (&$sent): void {
                $concerns = Concern::query()
                    ->assignedTo($teacher->id)
                    ->whereNotIn('status', [ConcernStatus::Resolved, ConcernStatus::Closed])
                    ->with('category')
                    ->oldest('status_changed_at')
                    ->get();

                if ($concerns->isEmpty()) {
                    return;
                }

                Mail::to($teacher)->send(new DailyTicketDigestMail($teacher, $concerns));
                $sent++;
            });

        $this->info("Sent {$sent} daily ticket digest(s).");

        return self::SUCCESS;
    }
}
