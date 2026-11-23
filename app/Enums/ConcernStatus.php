<?php

namespace App\Enums;

enum ConcernStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('statuses.'.$this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-900',
            self::UnderReview => 'bg-sage-100 text-spruce-700',
            self::InProgress => 'bg-spruce-100 text-spruce-800',
            self::Resolved => 'bg-sage-200 text-sage-800',
            self::Closed => 'bg-slate-200 text-slate-700',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Closed;
    }

    public function canTransitionTo(self $status): bool
    {
        return $status === $this || in_array($status->value, $this->allowedTransitions(), true);
    }

    public function next(): ?self
    {
        $next = $this->allowedTransitions()[0] ?? null;

        return $next === null ? null : self::from($next);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::UnderReview->value],
            self::UnderReview => [self::InProgress->value],
            self::InProgress => [self::Resolved->value],
            self::Resolved => [self::Closed->value],
            self::Closed => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
