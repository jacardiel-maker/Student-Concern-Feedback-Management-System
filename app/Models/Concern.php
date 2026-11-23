<?php

namespace App\Models;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use Database\Factories\ConcernFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'ticket_no',
    'user_id',
    'category_id',
    'recipient_id',
    'subject',
    'description',
    'priority',
    'status',
    'status_changed_at',
    'is_anonymous',
    'resolved_at',
    'closed_at',
])]
class Concern extends Model
{
    public const int SLA_BUSINESS_DAYS = 5;

    /** @use HasFactory<ConcernFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => ConcernPriority::class,
            'status' => ConcernStatus::class,
            'status_changed_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class)->oldest();
    }

    public function publicReplies(): HasMany
    {
        return $this->hasMany(Reply::class)->where('is_internal_note', false)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->latest();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    public function isSlaBreached(): bool
    {
        if (in_array($this->status, [ConcernStatus::Resolved, ConcernStatus::Closed], true)) {
            return false;
        }

        return $this->businessDaysSinceStatusChange() >= self::SLA_BUSINESS_DAYS;
    }

    public function businessDaysSinceStatusChange(): int
    {
        $start = ($this->status_changed_at ?? $this->created_at)?->copy()->startOfDay();

        if ($start === null) {
            return 0;
        }

        $end = now()->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        $businessDays = 0;
        $cursor = $start->copy();

        while ($cursor->lessThan($end)) {
            $cursor->addDay();

            if (! $cursor->isWeekend()) {
                $businessDays++;
            }
        }

        return $businessDays;
    }

    public static function slaCutoffDate(): Carbon
    {
        $cutoff = now()->startOfDay();
        $remaining = self::SLA_BUSINESS_DAYS;

        while ($remaining > 0) {
            $cutoff->subDay();

            if (! $cutoff->isWeekend()) {
                $remaining--;
            }
        }

        return $cutoff->endOfDay();
    }

    public function isAnonymous(): bool
    {
        return $this->is_anonymous || $this->user_id === null;
    }

    public function submitterLabelFor(?User $user): string
    {
        if (! $this->isAnonymous()) {
            return $this->author?->name ?? __('labels.unknown');
        }

        if ($user !== null && $this->user_id === $user->id) {
            return $this->author?->name ?? __('labels.unknown');
        }

        return __('labels.anonymous');
    }

    public function isRateable(): bool
    {
        return $this->status === ConcernStatus::Resolved && $this->rating === null;
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $this->recipient_id === $user->id;
        }

        return $this->user_id === $user->id;
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isTeacher()) {
            return $query->where('recipient_id', $user->id);
        }

        return $query->where('user_id', $user->id);
    }

    #[Scope]
    protected function withFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('ticket_no', 'like', $search)
                    ->orWhere('subject', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('concerns.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('concerns.created_at', '<=', $filters['to']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query;
    }

    #[Scope]
    protected function slaBreached(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [ConcernStatus::Resolved, ConcernStatus::Closed])
            ->whereNotNull('status_changed_at')
            ->where('status_changed_at', '<=', self::slaCutoffDate());
    }

    #[Scope]
    protected function unassigned(Builder $query): Builder
    {
        return $query->whereNull('recipient_id');
    }

    #[Scope]
    protected function assignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_id', $userId);
    }
}
