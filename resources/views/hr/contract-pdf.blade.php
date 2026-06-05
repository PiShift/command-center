<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrat de Travail — {{ $contract->employee->employee_number }}</title>
<style>
  @page { margin: 15mm 20mm 20mm 20mm; }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.5;
  }

  .page-body {
    padding: 0 0 30mm 0;
  }

  /* ── Company header ── */
  .header-table { width: 100%; margin-bottom: 32px; border-collapse: collapse; }
  .header-logo-cell { width: 50%; vertical-align: top; }
  .header-meta-cell { width: 50%; vertical-align: top; text-align: right; }
  .company-name { font-size: 16px; font-weight: bold; color: #141413; margin-bottom: 4px; }
  .company-detail { font-size: 8.5px; color: #888; line-height: 1.9; }

  /* ── Contract title ── */
  .contract-title-block {
    text-align: center;
    margin: 28px 0 24px 0;
    border-top: 2px solid #141413;
    border-bottom: 2px solid #141413;
    padding: 14px 0;
  }
  .contract-title {
    font-size: 15px;
    font-weight: bold;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #141413;
  }
  .contract-ref {
    font-size: 8.5px;
    color: #aaa;
    letter-spacing: 0.06em;
    margin-top: 4px;
  }

  /* ── Parties table ── */
  .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; border: 0.5pt solid #ddd; }
  .party-cell { width: 50%; vertical-align: top; padding: 8pt; font-size: 9.5pt; }
  .party-cell:last-child { border-left: 0.5pt solid #ddd; }
  .party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; color: #aaa; margin-bottom: 6px; }
  .party-name { font-size: 12px; font-weight: bold; color: #141413; margin-bottom: 3px; }
  .party-detail { font-size: 9px; color: #555; line-height: 1.8; }

  /* ── Articles ── */
  .article {
    margin-bottom: 16px;
    page-break-inside: avoid;
  }
  .article-title {
    font-size: 10.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #141413;
    border-bottom: 1px solid #eeeee9;
    padding-bottom: 4px;
    margin: 10pt 0 4pt 0;
  }
  .article-body {
    font-size: 10pt;
    color: #333;
    line-height: 1.7;
    text-align: justify;
    margin: 0 0 6pt 0;
  }
  .article-body strong { color: #141413; }

  /* ── Signature block ── */
  .signature-block {
    margin-top: 30pt;
    page-break-inside: avoid;
  }
  .sig-table { width: 100%; border-collapse: collapse; }
  .sig-cell { width: 50%; vertical-align: top; padding-right: 24px; font-size: 9.5pt; }
  .sig-cell:last-child { padding-right: 0; padding-left: 24px; }
  .sig-label { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #888; margin-bottom: 6px; }
  .sig-name { font-size: 11px; font-weight: bold; color: #141413; margin-bottom: 2px; }
  .sig-title { font-size: 9px; color: #666; margin-bottom: 4px; }
  .sig-date { font-size: 9px; color: #888; margin-bottom: 16px; }
  .sig-line {
    border-bottom: 0.5pt solid #333;
    width: 80%;
    margin-top: 20pt;
    padding-bottom: 4pt;
    font-size: 8px;
    color: #aaa;
  }

  /* ── Footer ── */
  #footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20mm;
    font-size: 8pt;
    color: #888;
    border-top: 0.5pt solid #ddd;
    padding-top: 4pt;
    padding-left: 20mm;
    padding-right: 20mm;
  }

  .divider { border: none; border-top: 1px solid #eeeee9; margin: 20px 0; }
</style>
</head>
<body>

@php
    $employee  = $contract->employee;
    $user      = $employee->user;
    $typeLabel = match($contract->employment_type) {
        'CDI'        => 'Contrat à Durée Indéterminée (CDI)',
        'CDD'        => 'Contrat à Durée Déterminée (CDD)',
        'freelance'  => 'Contrat Freelance / Prestation de Services',
        'internship' => 'Convention de Stage',
        'part_time'  => 'Contrat à Temps Partiel',
        default      => $contract->employment_type,
    };
    $companyName    = config('app.company_name', 'PiShift SARL');
    $companyAddress = config('app.company_address', 'Nouakchott, Mauritanie');
    $companyReg     = config('app.company_registration', 'RC: 00000');
@endphp

<div class="page-body">

  {{-- Footer (fixed, bottom of every page) --}}
  <div id="footer">
    <table width="100%"><tr>
      <td>{{ $companyName }} — {{ $typeLabel }}</td>
      <td align="center">Réf. : {{ $employee->employee_number }}-{{ $contract->effective_from->format('Ym') }}</td>
      <td align="right">Généré le {{ now()->format('d/m/Y') }}</td>
    </tr></table>
  </div>

  {{-- Company header --}}
  <table class="header-table">
    <tr>
      <td class="header-logo-cell">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-detail">
          {{ $companyAddress }}<br>
          {{ $companyReg }}
        </div>
      </td>
      <td class="header-meta-cell">
        <div class="company-detail">
          Réf. contrat : {{ $employee->employee_number }}-{{ $contract->effective_from->format('Ym') }}<br>
          Date d'émission : {{ now()->format('d/m/Y') }}<br>
          Statut : {{ ucfirst($contract->status) }}
        </div>
      </td>
    </tr>
  </table>

  {{-- Contract title --}}
  <div class="contract-title-block">
    <div class="contract-title">{{ $typeLabel }}</div>
    <div class="contract-ref">Entre {{ $companyName }} et {{ $user->name }}</div>
  </div>

  {{-- Parties --}}
  <table class="parties-table">
    <tr>
      <td class="party-cell">
        <div class="party-label">L'Employeur</div>
        <div class="party-name">{{ $companyName }}</div>
        <div class="party-detail">
          {{ $companyAddress }}<br>
          {{ $companyReg }}<br>
          Représentant légal
        </div>
      </td>
      <td class="party-cell">
        <div class="party-label">L'Employé(e)</div>
        <div class="party-name">{{ $user->name }}</div>
        <div class="party-detail">
          Numéro employé : {{ $employee->employee_number }}<br>
          @if($employee->personal_email) E-mail : {{ $employee->personal_email }}<br>@endif
          @if($employee->personal_phone) Tél. : {{ $employee->personal_phone }}<br>@endif
          @if($employee->address) {{ $employee->address }}@endif
        </div>
      </td>
    </tr>
  </table>

  <hr class="divider">

  {{-- Article 1 — Parties --}}
  <div class="article">
    <div class="article-title">Article 1 — Parties</div>
    <div class="article-body">
      Le présent contrat est conclu entre <strong>{{ $companyName }}</strong>, ci-après dénommée « l'Employeur »,
      et <strong>{{ $user->name }}</strong>, ci-après dénommé(e) « l'Employé(e) ».
    </div>
  </div>

  {{-- Article 2 — Position & Department --}}
  <div class="article">
    <div class="article-title">Article 2 — Poste et Département</div>
    <div class="article-body">
      L'Employé(e) est engagé(e) en qualité de
      <strong>{{ $employee->job_title ?: 'à définir' }}</strong>
      @if($employee->department), au sein du département <strong>{{ $employee->department }}</strong>@endif.
      L'Employé(e) exercera ses fonctions sous la supervision de la direction de l'Employeur.
    </div>
  </div>

  {{-- Article 3 — Type & Duration --}}
  <div class="article">
    <div class="article-title">Article 3 — Nature et Durée du Contrat</div>
    <div class="article-body">
      Le présent contrat est un <strong>{{ $typeLabel }}</strong>.
      Il prend effet le <strong>{{ $contract->effective_from->format('d/m/Y') }}</strong>.
      @if($contract->effective_to)
        Il est conclu pour une durée déterminée et prendra fin le <strong>{{ $contract->effective_to->format('d/m/Y') }}</strong>.
      @else
        Il est conclu pour une durée <strong>indéterminée</strong>.
      @endif
    </div>
  </div>

  {{-- Article 4 — Working Hours --}}
  <div class="article">
    <div class="article-title">Article 4 — Durée et Organisation du Travail</div>
    <div class="article-body">
      L'Employé(e) travaillera <strong>{{ $contract->working_hours_per_day }} heures par jour</strong>,
      <strong>{{ $contract->working_days_per_week }} jours par semaine</strong>,
      soit {{ number_format($contract->working_hours_per_day * $contract->working_days_per_week, 1) }} heures hebdomadaires.
      Les horaires et jours de travail seront définis en accord avec l'Employeur.
    </div>
  </div>

  {{-- Article 5 — Remuneration --}}
  <div class="article">
    <div class="article-title">Article 5 — Rémunération</div>
    <div class="article-body">
      En contrepartie des services rendus, l'Employé(e) percevra une rémunération brute mensuelle de
      <strong>{{ number_format($contract->base_salary, 2) }} {{ $contract->currency }}</strong>.
      Le paiement sera effectué mensuellement, à terme échu.
    </div>
  </div>

  {{-- Article 6 — Notice Period --}}
  <div class="article">
    <div class="article-title">Article 6 — Préavis</div>
    <div class="article-body">
      En cas de rupture du présent contrat par l'une ou l'autre des parties (sauf faute grave), un préavis de
      <strong>{{ $contract->notice_period_days }} jours</strong> devra être respecté.
    </div>
  </div>

  @if($contract->additional_clauses)
  {{-- Article 7 — Additional Clauses --}}
  <div class="article">
    <div class="article-title">Article 7 — Clauses Particulières</div>
    <div class="article-body">{{ $contract->additional_clauses }}</div>
  </div>
  @endif

  {{-- Article 8 — Governing Law --}}
  <div class="article">
    <div class="article-title">Article {{ $contract->additional_clauses ? '8' : '7' }} — Droit Applicable</div>
    <div class="article-body">
      Le présent contrat est soumis aux dispositions du Code du Travail en vigueur en République Islamique de Mauritanie.
      Tout litige relatif à l'exécution ou à la résiliation du présent contrat sera soumis aux juridictions compétentes du lieu du siège social de l'Employeur.
    </div>
  </div>

  {{-- Signature block --}}
  <div class="signature-block">
    <table class="sig-table">
      <tr>
        <td class="sig-cell">
          <div class="sig-label">Pour l'Employeur</div>
          <div class="sig-name">{{ $companyName }}</div>
          <div class="sig-title">Direction Générale</div>
          <div class="sig-date">Fait à Nouakchott, le {{ now()->format('d/m/Y') }}</div>
          <div class="sig-line">Signature &amp; Cachet</div>
        </td>
        <td class="sig-cell">
          <div class="sig-label">L'Employé(e)</div>
          <div class="sig-name">{{ $user->name }}</div>
          <div class="sig-title">Lu et approuvé</div>
          <div class="sig-date">Fait à Nouakchott, le {{ now()->format('d/m/Y') }}</div>
          <div class="sig-line">Signature</div>
        </td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>
