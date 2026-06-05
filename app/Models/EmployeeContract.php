<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmployeeContract extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'template_id',
        'contract_reference',
        'employment_type',
        'base_salary',
        'currency',
        'working_hours_per_day',
        'working_days_per_week',
        'notice_period_days',
        'effective_from',
        'effective_to',
        'additional_clauses',
        'status',
    ];

    protected $casts = [
        'effective_from'        => 'date',
        'effective_to'          => 'date',
        'base_salary'           => 'decimal:2',
        'working_hours_per_day' => 'decimal:1',
    ];

    // ── Boot: auto-generate contract_reference on creating ──────────────────
    protected static function booted(): void
    {
        static::creating(function (EmployeeContract $contract) {
            if (empty($contract->contract_reference)) {
                $year  = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $contract->contract_reference = 'CTR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    // ── Media ────────────────────────────────────────────────────────────────
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('signed_contract')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void {}

    // ── Methods ──────────────────────────────────────────────────────────────
    public function activate(): void
    {
        // Close any currently active contract for this employee
        static::where('employee_id', $this->employee_id)
            ->where('status', 'active')
            ->where('id', '!=', $this->id)
            ->each(function (EmployeeContract $contract) {
                $contract->update([
                    'status'       => 'terminated',
                    'effective_to' => now()->toDateString(),
                ]);
            });

        $this->update(['status' => 'active']);
    }

    public function terminate(): void
    {
        $this->update([
            'status'       => 'terminated',
            'effective_to' => now()->toDateString(),
        ]);
    }
}
