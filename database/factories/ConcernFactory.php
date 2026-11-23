<?php

namespace Database\Factories;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Concern>
 */
class ConcernFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_no' => 'SC-'.now()->year.'-'.fake()->unique()->numberBetween(1000, 99999),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'recipient_id' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(ConcernPriority::cases()),
            'status' => ConcernStatus::Pending,
            'status_changed_at' => now(),
            'is_anonymous' => false,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_anonymous' => true,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ConcernStatus::Resolved,
            'resolved_at' => now()->subDay(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ConcernStatus::Closed,
            'resolved_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
    }
}
