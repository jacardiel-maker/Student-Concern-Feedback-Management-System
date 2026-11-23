<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'name_fil' => null,
            'is_urgent' => false,
            'is_active' => true,
        ];
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => ['is_urgent' => true]);
    }
}
