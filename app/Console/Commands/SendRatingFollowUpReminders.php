<?php

namespace App\Console\Commands;

use App\Enums\ConcernStatus;
use App\Models\Concern;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-rating-follow-up-reminders')]
#[Description('Remind students to rate concerns resolved three days ago.')]
class SendRatingFollowUpReminders extends Command
{
    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sent = 0;

        Concern::query()
            ->where('status', ConcernStatus::Resolved)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subDays(3))
            ->whereDoesntHave('rating')
            ->whereDoesntHave('notifications', fn ($query) => $query->where('type', 'rating_reminder'))
            ->with('author')
            ->each(function (Concern $concern) use (&$sent): void {
                $student = $concern->author;

                if ($student === null || ! $student->is_active) {
                    return;
                }

                $this->notifications->send(
                    $student,
                    'rating_reminder',
                    __('messages.rating_reminder_title'),
                    __('messages.rating_reminder_body', ['ticket' => $concern->ticket_no]),
                    $concern,
                );

                $sent++;
            });

        $this->info("Sent {$sent} rating reminder(s).");

        return self::SUCCESS;
    }
}
