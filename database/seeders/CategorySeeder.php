<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Academic', 'name_fil' => 'Akademiko', 'is_urgent' => false],
            ['name' => 'Bullying', 'name_fil' => 'Pambu-bully', 'is_urgent' => true],
            ['name' => 'Facilities', 'name_fil' => 'Pasilidad', 'is_urgent' => false],
            ['name' => 'Grades', 'name_fil' => 'Marka', 'is_urgent' => false],
            ['name' => 'Teacher-Related', 'name_fil' => 'Kaugnay ng Guro', 'is_urgent' => false],
            ['name' => 'Safety', 'name_fil' => 'Kaligtasan', 'is_urgent' => true],
            ['name' => 'Others', 'name_fil' => 'Iba pa', 'is_urgent' => false],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
