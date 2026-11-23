<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'student' => 'Submits and tracks concerns',
            'teacher' => 'Receives, replies to, and updates assigned concerns',
            'admin' => 'Manages users, categories, assignments, and reports',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
