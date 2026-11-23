<?php

namespace App\Mail;

use App\Models\Concern;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyTicketDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Concern>  $concerns
     */
    public function __construct(
        public readonly User $recipient,
        public readonly Collection $concerns,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.digest_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-ticket-digest',
        );
    }
}
