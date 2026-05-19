<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Resolution order for every action:
     *  1. Super-admin role → always allowed
     *  2. User holds the required permission → allowed
     *  3. Deny
     */

    // ── View ──────────────────────────────────────────────────────────────────

    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.view');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.create');
    }

    public function store(User $user): bool
    {
        return $this->create($user);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(User $user, Expense $expense): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.create');
    }

    // ── Confirm ───────────────────────────────────────────────────────────────

    public function confirm(User $user, Expense $expense): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.confirm');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(User $user, Expense $expense): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.manage');
    }

    // ── Manage Categories ─────────────────────────────────────────────────────

    public function manageCategories(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.manage');
    }

    // ── Manage Recurring Charges ──────────────────────────────────────────────

    public function manageRecurring(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.manage');
    }

    // ── Manage Budgets ────────────────────────────────────────────────────────

    public function manageBudgets(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $user->hasPermission('expenses.manage');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function isSuperAdmin(User $user): bool
    {
        return $user->roleModel?->isSuperAdmin() ?? false;
    }
}
