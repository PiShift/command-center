<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmployeeProfile extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'employee_number',
        'job_title',
        'department',
        'employment_type',
        'status',
        'start_date',
        'end_date',
        'personal_phone',
        'personal_email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'nni',
        'nationality',
        'date_of_birth',
        'work_location',
        'category',
        'probation_period_months',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'date_of_birth' => 'date',
        'status'        => 'string',
    ];

    // ── Boot: auto-generate employee_number on creating ──────────────────────
    protected static function booted(): void
    {
        static::creating(function (EmployeeProfile $profile) {
            if (empty($profile->employee_number)) {
                $count = static::withTrashed()->count() + 1;
                $profile->employee_number = 'EMP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class, 'employee_id')->orderByDesc('effective_from');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class, 'employee_id');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class, 'employee_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class, 'employee_id');
    }

    public function getPendingAdvancesTotal(): float
    {
        return (float) $this->advances()->pending()->sum('amount');
    }

    public function getActiveLoansList(): Collection
    {
        return $this->loans()
            ->active()
            ->get()
            ->filter(fn (EmployeeLoan $loan) => $loan->amount_remaining > 0)
            ->values();
    }

    // ── Accessors ────────────────────────────────────────────────────────────
    public function getCurrentContractAttribute(): ?EmployeeContract
    {
        return $this->contracts->firstWhere('status', 'active')
            ?? $this->contracts->first();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }

    // ── Media ────────────────────────────────────────────────────────────────
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // No conversions needed for avatars in this phase
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }
}
