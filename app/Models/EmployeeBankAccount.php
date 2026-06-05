<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankAccount extends Model
{
    protected $fillable = [
        'employee_id',
        'bank_name',
        'account_number',
        'account_holder_name',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (EmployeeBankAccount $bankAccount) {
            if (! $bankAccount->is_primary || ! $bankAccount->employee_id) {
                return;
            }

            static::where('employee_id', $bankAccount->employee_id)
                ->where('id', '!=', $bankAccount->id)
                ->update(['is_primary' => false]);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }
}
