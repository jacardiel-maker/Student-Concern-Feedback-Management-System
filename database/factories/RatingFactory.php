<?php

namespace Database\Factories;

use App\Models\Concern;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concern_id' => Concern::factory(),
            'user_id' => User::factory(),
            'score' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}
