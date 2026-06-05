<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // PermissionSeeder::class,
            // RoleSeeder::class,
            // AdminUserSeeder::class,
            // ExpenseCategorySeeder::class,
            CompanyBankAccountSeeder::class,
        ]);

    }
}
