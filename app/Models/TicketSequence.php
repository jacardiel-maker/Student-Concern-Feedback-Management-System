<?php

namespace App\Models;

use Database\Factories\TicketSequenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSequence extends Model
{
    /** @use HasFactory<TicketSequenceFactory> */
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'ticket_sequences';

    protected $primaryKey = 'year';

    protected $keyType = 'int';

    protected $fillable = ['year', 'last_number'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
