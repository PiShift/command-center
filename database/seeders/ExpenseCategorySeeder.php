<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salaries',       'color' => '#D97757', 'icon' => '💼', 'sort_order' => 1],
            ['name' => 'Rent',           'color' => '#4a90d9', 'icon' => '🏢', 'sort_order' => 2],
            ['name' => 'Infrastructure', 'color' => '#7b5ea7', 'icon' => '🖥️', 'sort_order' => 3],
            ['name' => 'Software',       'color' => '#3d9970', 'icon' => '💻', 'sort_order' => 4],
            ['name' => 'Marketing',      'color' => '#e67e22', 'icon' => '📣', 'sort_order' => 5],
            ['name' => 'Travel',         'color' => '#27ae60', 'icon' => '✈️', 'sort_order' => 6],
            ['name' => 'Misc',           'color' => '#8c8c8a', 'icon' => '📦', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_system' => true])
            );
        }
    }
}
