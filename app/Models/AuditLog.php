<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'concern_id', 'action', 'old_value', 'new_value', 'reason', 'ip_address'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function concern(): BelongsTo
    {
        return $this->belongsTo(Concern::class);
    }

    #[Scope]
    protected function forConcern(Builder $query, int $concernId): Builder
    {
        return $query->where('concern_id', $concernId);
    }

    #[Scope]
    protected function ofAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
}
