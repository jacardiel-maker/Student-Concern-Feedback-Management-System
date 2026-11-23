<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::where('name', 'student')->value('id') ?? 1,
            'student_number' => 'ST-'.fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'department' => fake()->randomElement(['Computer Science', 'Engineering', 'Business', 'Education']),
            'is_active' => true,
            'email_opt_in' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => Role::where('name', 'student')->value('id') ?? 1,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => Role::where('name', 'teacher')->value('id') ?? 2,
            'student_number' => null,
            'department' => fake()->randomElement(['Computer Science', 'Engineering', 'Guidance Office']),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => Role::where('name', 'admin')->value('id') ?? 3,
            'student_number' => null,
            'department' => 'Student Affairs',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
