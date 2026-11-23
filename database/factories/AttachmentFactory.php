<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concern_id' => Concern::factory(),
            'reply_id' => null,
            'user_id' => User::factory(),
            'original_name' => fake()->word().'.pdf',
            'stored_path' => now()->format('Y/m').'/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 512000),
            'created_at' => now(),
        ];
    }
}
