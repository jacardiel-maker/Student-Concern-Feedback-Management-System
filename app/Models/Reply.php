<?php

namespace App\Models;

use Database\Factories\ReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['concern_id', 'user_id', 'body', 'is_internal_note'])]
class Reply extends Model
{
    /** @use HasFactory<ReplyFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function concern(): BelongsTo
    {
        return $this->belongsTo(Concern::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function authorLabelFor(?User $viewer, Concern $concern): string
    {
        if ($viewer !== null && $this->user_id === $viewer->id) {
            return $this->user?->name ?? __('labels.unknown');
        }

        if ($concern->isAnonymous() && $this->user?->isStudent()) {
            return __('labels.anonymous');
        }

        return $this->user?->name ?? __('labels.unknown');
    }

    #[Scope]
    protected function internal(Builder $query): Builder
    {
        return $query->where('is_internal_note', true);
    }

    #[Scope]
    protected function public(Builder $query): Builder
    {
        return $query->where('is_internal_note', false);
    }
}
