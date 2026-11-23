<?php

namespace App\Mail;

use App\Models\Concern;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConcernNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $notificationTitle,
        public readonly string $notificationBody,
        public readonly ?Concern $concern = null,
    ) {}

    public function build(): self
    {
        return $this->subject($this->notificationTitle)
            ->view('emails.concern-notification', [
                'notificationTitle' => $this->notificationTitle,
                'notificationBody' => $this->notificationBody,
                'concern' => $this->concern,
            ]);
    }
}
