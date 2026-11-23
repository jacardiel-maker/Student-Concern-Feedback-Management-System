<?php

namespace App\Models;

use App\Enums\StudentApprovalStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'student_number', 'name', 'email', 'password', 'department', 'is_active', 'approval_status', 'email_opt_in', 'last_visited_concern_id', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'approval_status' => StudentApprovalStatus::class,
            'email_opt_in' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function concerns(): HasMany
    {
        return $this->hasMany(Concern::class);
    }

    public function assignedConcerns(): HasMany
    {
        return $this->hasMany(Concern::class, 'recipient_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(string $name): bool
    {
        return $this->role?->name === $name;
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAwaitingApproval(): bool
    {
        return $this->approval_status === StudentApprovalStatus::Pending;
    }

    public function displayName(): string
    {
        return $this->name;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function awaitingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', StudentApprovalStatus::Pending);
    }

    #[Scope]
    protected function withRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('role', fn (Builder $roleQuery) => $roleQuery->where('name', $role));
    }

    public function preferredLocale(): string
    {
        $locale = session('locale');

        return is_string($locale) && in_array($locale, ['en', 'fil'], true)
            ? $locale
            : config('app.locale');
    }
}
