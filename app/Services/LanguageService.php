<?php

namespace App\Services;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;

class LanguageService
{
    /** @var array<string, string> */
    private const SUPPORTED_LOCALES = [
        'en' => 'English',
        'fil' => 'Filipino',
    ];

    /**
     * @return array<string, string>
     */
    public function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::SUPPORTED_LOCALES);
    }

    public function current(): string
    {
        return app()->getLocale();
    }

    public function switchTo(string $locale): bool
    {
        if (! $this->isSupported($locale)) {
            return false;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return true;
    }

    public function initialize(): void
    {
        $locale = session('locale', config('app.locale'));

        if (is_string($locale) && $this->isSupported($locale)) {
            app()->setLocale($locale);
        }
    }

    public function label(string $group, string $key): string
    {
        return __($group.'.'.$key);
    }

    public function statusLabel(ConcernStatus|string $status): string
    {
        $value = $status instanceof ConcernStatus ? $status->value : $status;

        return __('statuses.'.$value);
    }

    public function priorityLabel(ConcernPriority|string $priority): string
    {
        $value = $priority instanceof ConcernPriority ? $priority->value : $priority;

        return __('labels.priority_'.$value);
    }
}
