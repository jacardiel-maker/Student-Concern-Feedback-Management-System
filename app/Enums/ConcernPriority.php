<?php

namespace App\Enums;

enum ConcernPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __('labels.priority_'.$this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700',
            self::Normal => 'bg-sky-100 text-sky-700',
            self::High => 'bg-orange-100 text-orange-800',
            self::Urgent => 'bg-clay-100 text-clay-800',
        };
    }

    public function requiresEscalation(): bool
    {
        return $this === self::Urgent;
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
