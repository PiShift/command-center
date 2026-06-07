<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Congé Annuel',
                'code' => 'annual',
                'color' => '#3b82f6',
                'is_paid' => true,
                'requires_approval' => true,
                'accrues_monthly' => true,
                'monthly_accrual_days' => 2.0,
                'default_days_per_year' => 24,
                'is_system' => true,
            ],
            [
                'name' => 'Congé Maladie',
                'code' => 'sick',
                'color' => '#f59e0b',
                'is_paid' => true,
                'requires_approval' => false,
                'accrues_monthly' => false,
                'monthly_accrual_days' => 0,
                'default_days_per_year' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Congé Sans Solde',
                'code' => 'unpaid',
                'color' => '#6b7280',
                'is_paid' => false,
                'requires_approval' => true,
                'accrues_monthly' => false,
                'monthly_accrual_days' => 0,
                'default_days_per_year' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Congé Maternité',
                'code' => 'maternity',
                'color' => '#ec4899',
                'is_paid' => true,
                'requires_approval' => true,
                'accrues_monthly' => false,
                'monthly_accrual_days' => 0,
                'default_days_per_year' => 98,
                'is_system' => true,
            ],
            [
                'name' => 'Congé Exceptionnel',
                'code' => 'exceptional',
                'color' => '#8b5cf6',
                'is_paid' => true,
                'requires_approval' => true,
                'accrues_monthly' => false,
                'monthly_accrual_days' => 0,
                'default_days_per_year' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Congé Paternité',
                'code' => 'paternity',
                'color' => '#06b6d4',
                'is_paid' => true,
                'requires_approval' => true,
                'accrues_monthly' => false,
                'monthly_accrual_days' => 0,
                'default_days_per_year' => 3,
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }

        $leaveService = app(LeaveService::class);

        EmployeeProfile::query()
            ->active()
            ->with(['contracts' => fn ($query) => $query->where('status', 'active')->orderByDesc('effective_from')])
            ->get()
            ->each(fn (EmployeeProfile $employee) => $leaveService->initializeBalancesForEmployee($employee, now()->year));
    }
}
