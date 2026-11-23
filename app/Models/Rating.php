<?php

namespace App\Models;

use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['concern_id', 'user_id', 'score', 'comment'])]
class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
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
}
