<?php

namespace Database\Factories;

use App\Models\Concern;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'concern_id' => Concern::factory(),
            'type' => fake()->randomElement(['new_reply', 'status_change', 'urgent_alert', 'assigned', 'rated']),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(8),
            'is_read' => false,
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
