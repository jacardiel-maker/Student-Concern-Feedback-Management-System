<?php

namespace Database\Factories;

use App\Models\Concern;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concern_id' => Concern::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_internal_note' => false,
            'created_at' => now(),
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => ['is_internal_note' => true]);
    }
}
