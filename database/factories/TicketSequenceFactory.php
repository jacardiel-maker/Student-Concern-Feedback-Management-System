<?php

namespace Database\Factories;

use App\Models\TicketSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSequence>
 */
class TicketSequenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => now()->year,
            'last_number' => fake()->numberBetween(1, 9999),
        ];
    }
}
