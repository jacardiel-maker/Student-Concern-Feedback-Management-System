<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'concern_id' => Concern::factory(),
            'action' => fake()->randomElement(['created', 'status_changed', 'assigned', 'unmasked', 'rated']),
            'old_value' => null,
            'new_value' => null,
            'reason' => null,
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
