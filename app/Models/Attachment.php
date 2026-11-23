<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['concern_id', 'reply_id', 'user_id', 'original_name', 'stored_path', 'mime_type', 'size_bytes'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function concern(): BelongsTo
    {
        return $this->belongsTo(Concern::class);
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(Reply::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
