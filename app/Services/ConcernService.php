<?php

namespace App\Services;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Rating;
use App\Models\Reply;
use App\Models\TicketSequence;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConcernService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $author): Concern
    {
        return DB::transaction(function () use ($data, $author): Concern {
            $category = Category::active()->findOrFail($data['category_id']);
            $recipient = empty($data['recipient_id'])
                ? null
                : User::active()->withRole('teacher')->findOrFail($data['recipient_id']);
            $priority = ConcernPriority::from($data['priority'] ?? ConcernPriority::Normal->value);

            if ($category->is_urgent && $priority !== ConcernPriority::Urgent) {
                $priority = ConcernPriority::Urgent;
            }

            $isAnonymous = (bool) ($data['is_anonymous'] ?? false);

            $concern = Concern::create([
                'ticket_no' => $this->generateTicketNumber(),
                'user_id' => $author->id,
                'category_id' => $category->id,
                'recipient_id' => $recipient?->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $priority,
                'status' => ConcernStatus::Pending,
                'status_changed_at' => now(),
                'is_anonymous' => $isAnonymous,
            ]);

            $this->audit->record('created', $author, $concern, null, ConcernStatus::Pending->value);

            $this->notifications->notifyAdministrators(
                __('messages.new_concern_title'),
                __('messages.new_concern_body', ['ticket' => $concern->ticket_no]),
                $concern,
            );

            if ($recipient !== null) {
                $this->assign($concern, $recipient, $author);
            }

            if ($priority->requiresEscalation()) {
                $this->escalate($concern, $author);
            }

            return $concern;
        });
    }

    public function generateTicketNumber(): string
    {
        $year = (int) now()->year;

        return DB::transaction(function () use ($year): string {
            $sequence = TicketSequence::query()
                ->lockForUpdate()
                ->firstOrCreate(['year' => $year], ['last_number' => 0]);

            $sequence->increment('last_number');

            return sprintf('SC-%d-%04d', $year, $sequence->last_number);
        });
    }

    public function updateStatus(
        Concern $concern,
        ConcernStatus $newStatus,
        User $actor,
        ?string $note = null,
    ): Concern {
        $currentStatus = $concern->status;

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                __('messages.invalid_transition', ['from' => $currentStatus->label(), 'to' => $newStatus->label()]),
            );
        }

        DB::transaction(function () use ($concern, $newStatus, $actor, $note, $currentStatus): void {
            $attributes = [
                'status' => $newStatus,
                'status_changed_at' => now(),
            ];

            if ($newStatus === ConcernStatus::Resolved && $concern->resolved_at === null) {
                $attributes['resolved_at'] = now();
            }

            if ($newStatus === ConcernStatus::Closed && $concern->closed_at === null) {
                $attributes['closed_at'] = now();
            }

            $concern->update($attributes);

            $this->audit->record(
                'status_changed',
                $actor,
                $concern,
                $currentStatus->value,
                $newStatus->value,
                $note,
            );

            if ($concern->user_id !== $actor->id) {
                $this->notifyAuthor(
                    $concern,
                    __('messages.status_changed'),
                    __('messages.status_changed_body', [
                        'ticket' => $concern->ticket_no,
                        'status' => $newStatus->label(),
                    ]),
                );
            }
        });

        return $concern->refresh();
    }

    public function assign(Concern $concern, User $teacher, User $actor): Concern
    {
        $previousId = $concern->recipient_id;

        DB::transaction(function () use ($concern, $teacher, $actor, $previousId): void {
            $concern->update(['recipient_id' => $teacher->id]);

            $this->audit->record(
                'assigned',
                $actor,
                $concern,
                $previousId === null ? null : (string) $previousId,
                (string) $teacher->id,
            );

            $this->notifications->send(
                $teacher,
                'assigned',
                __('messages.assigned'),
                __('messages.assigned_body', ['ticket' => $concern->ticket_no]),
                $concern,
            );
        });

        return $concern->refresh();
    }

    public function unassign(Concern $concern, User $actor): Concern
    {
        $previousId = $concern->recipient_id;

        DB::transaction(function () use ($concern, $actor, $previousId): void {
            $concern->update(['recipient_id' => null]);

            $this->audit->record(
                'unassigned',
                $actor,
                $concern,
                $previousId === null ? null : (string) $previousId,
                null,
            );
        });

        return $concern->refresh();
    }

    public function addReply(Concern $concern, User $author, string $body, bool $isInternalNote = false): Reply
    {
        return DB::transaction(function () use ($concern, $author, $body, $isInternalNote): Reply {
            $reply = Reply::create([
                'concern_id' => $concern->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal_note' => $isInternalNote,
            ]);

            $this->audit->record(
                $isInternalNote ? 'internal_note_added' : 'reply_added',
                $author,
                $concern,
                null,
                null,
                mb_strimwidth($body, 0, 150, ''),
            );

            if ($isInternalNote) {
                $this->notifications->notifyAdministrators(
                    __('messages.internal_note_title'),
                    __('messages.internal_note_body', ['ticket' => $concern->ticket_no]),
                    $concern,
                );
            } else {
                $this->notifyAuthor(
                    $concern,
                    __('messages.new_reply'),
                    __('messages.new_reply_body', ['ticket' => $concern->ticket_no]),
                );

                if ($concern->recipient && $concern->recipient_id !== $author->id) {
                    $this->notifications->send(
                        $concern->recipient,
                        'new_reply',
                        __('messages.new_reply'),
                        __('messages.new_reply_body', ['ticket' => $concern->ticket_no]),
                        $concern,
                    );
                }
            }

            return $reply;
        });
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addAttachments(
        Concern $concern,
        User $user,
        array $files,
        ?Reply $reply = null,
    ): Collection {
        $attachments = new Collection;

        foreach ($files as $file) {
            $attachments->push(Attachment::create([
                'concern_id' => $concern->id,
                'reply_id' => $reply?->id,
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $file->store(now()->format('Y/m'), 'attachments'),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]));
        }

        $this->audit->record('attachment_added', $user, $concern, null, count($files).' file(s)');

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rate(Concern $concern, User $user, array $data): Rating
    {
        if ($concern->user_id !== $user->id || ! $concern->isRateable()) {
            throw new InvalidArgumentException(__('messages.rating_not_allowed'));
        }

        return DB::transaction(function () use ($concern, $user, $data): Rating {
            $rating = Rating::updateOrCreate(
                ['concern_id' => $concern->id],
                [
                    'user_id' => $user->id,
                    'score' => (int) $data['score'],
                    'comment' => $data['comment'] ?? null,
                ],
            );

            $this->audit->record('rated', $user, $concern, null, (string) $rating->score);

            $assignee = $concern->recipient;

            if ($assignee !== null) {
                $this->notifications->send(
                    $assignee,
                    'rated',
                    __('messages.rating_received'),
                    __('messages.rating_received_body', [
                        'ticket' => $concern->ticket_no,
                        'score' => $rating->score,
                    ]),
                    $concern,
                );
            }

            return $rating;
        });
    }

    public function escalate(Concern $concern, ?User $actor): void
    {
        DB::transaction(function () use ($concern, $actor): void {
            $this->audit->record('urgent_escalated', $actor, $concern, null, ConcernPriority::Urgent->value);

            $this->notifications->notifyAdministrators(
                __('messages.urgent_alert'),
                __('messages.urgent_alert_body', ['ticket' => $concern->ticket_no]),
                $concern,
            );
        });
    }

    private function notifyAuthor(Concern $concern, string $title, string $body): void
    {
        $author = $concern->author;

        if ($author) {
            $this->notifications->send($author, 'status_change', $title, $body, $concern);
        }
    }
}
