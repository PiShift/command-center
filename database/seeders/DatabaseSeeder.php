<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // $this->call(AdminUserSeeder::class);
        // $this->call(LeaveTypeSeeder::class);
        $this->call(ContractTemplateSeeder::class);
        // $this->call(RoleSeeder::class);
        // $this->call(PermissionSeeder::class);
        // $this->call(ExpenseCategorySeeder::class);
    }
}
