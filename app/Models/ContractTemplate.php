<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContractTemplate extends Model
{
    protected $fillable = [
        'name',
        'employment_type',
        'content',
        'language',
        'version',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where(function (Builder $q) use ($type) {
            $q->where('employment_type', $type)
              ->orWhere('employment_type', 'all');
        });
    }

    // ── Static helpers ───────────────────────────────────────────────────────

    public static function getDefaultForType(string $type): ?self
    {
        // Try specific type first
        $template = static::active()
            ->where('employment_type', $type)
            ->where('is_default', true)
            ->first();

        if ($template) {
            return $template;
        }

        // Fall back to 'all' type default
        return static::active()
            ->where('employment_type', 'all')
            ->where('is_default', true)
            ->first();
    }

    // ── Placeholder reference ────────────────────────────────────────────────

    public function getAvailablePlaceholders(): array
    {
        return [
            'company' => [
                '{{company_name}}'         => "Nom de l'entreprise",
                '{{company_address}}'      => 'Adresse',
                '{{company_phone}}'        => 'Téléphone',
                '{{company_email}}'        => 'Email',
                '{{company_registration}}' => "Numéro d'enregistrement",
                '{{representative_name}}'  => 'Nom du représentant',
                '{{representative_title}}' => 'Titre du représentant',
            ],
            'employee' => [
                '{{employee_name}}'        => 'Nom complet',
                '{{employee_nni}}'         => 'Numéro NNI',
                '{{employee_dob}}'         => 'Date de naissance',
                '{{employee_nationality}}' => 'Nationalité',
            ],
            'contract' => [
                '{{contract_type_label}}'  => 'Type de contrat (CDI, CDD...)',
                '{{job_title}}'            => 'Poste',
                '{{category}}'            => 'Catégorie / Grade',
                '{{base_salary}}'          => 'Salaire mensuel',
                '{{start_date}}'           => 'Date de début',
                '{{end_date}}'             => 'Date de fin (CDD)',
                '{{probation_period}}'     => "Période d'essai (mois)",
                '{{notice_period}}'        => 'Préavis',
                '{{work_location}}'        => 'Lieu de travail',
                '{{working_hours}}'        => 'Heures par jour',
                '{{working_days}}'         => 'Jours par semaine',
                '{{today_date}}'           => 'Date de génération',
                '{{contract_reference}}'   => 'Référence du contrat',
                '{{contract_duration}}'    => 'Durée (phrase complète)',
            ],
        ];
    }
}
