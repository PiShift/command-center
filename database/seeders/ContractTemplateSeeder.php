<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ContractTemplate::updateOrCreate(
            ['name' => 'CDI Standard — Mauritanien'],
            [
                'employment_type' => 'CDI',
                'language'        => 'fr',
                'version'         => 'v1.0',
                'is_default'      => true,
                'is_active'       => true,
                'content'         => $this->cdiContent(),
            ]
        );

            ContractTemplate::updateOrCreate(
              ['name' => 'CDD Standard — Mauritanien'],
              [
                'employment_type' => 'CDD',
                'language'        => 'fr',
                'version'         => 'v1.0',
                'is_default'      => true,
                'is_active'       => true,
                'content'         => $this->cddContent(),
              ]
            );
    }

    private function cdiContent(): string
    {
        return <<<'CONTENT'
<div style="text-align:center;margin-bottom:24pt;">
  <p style="font-size:10pt;color:#5c5c5a;margin-bottom:4pt;">{{company_name}} — {{company_address}}</p>
  <h1 style="font-size:16pt;font-weight:700;letter-spacing:1pt;margin:0">CONTRAT DE TRAVAIL</h1>
  <h2 style="font-size:13pt;font-weight:600;margin:4pt 0 0 0">À DURÉE INDÉTERMINÉE</h2>
  <p style="font-size:10pt;color:#5c5c5a;margin-top:6pt;">Réf. : {{contract_reference}}</p>
</div>

<table style="width:100%;margin-bottom:20pt;border:1px solid #e5e4df;border-collapse:collapse;">
  <tr>
    <td style="width:50%;padding:10pt;border:1px solid #e5e4df;vertical-align:top;">
      <p style="font-size:9pt;font-weight:700;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6pt;">L'EMPLOYEUR</p>
      <p style="margin:0;font-weight:700;">{{company_name}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">{{company_address}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Immatriculation : {{company_registration}}</p>
      <p style="margin:6pt 0 0 0;font-size:10pt;">Représentée par <strong>{{representative_name}}</strong>, {{representative_title}}</p>
    </td>
    <td style="width:50%;padding:10pt;border:1px solid #e5e4df;vertical-align:top;">
      <p style="font-size:9pt;font-weight:700;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6pt;">LE SALARIÉ</p>
      <p style="margin:0;font-weight:700;">{{employee_name}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">NNI : {{employee_nni}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Date de naissance : {{employee_dob}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Nationalité : {{employee_nationality}}</p>
    </td>
  </tr>
</table>

<p style="font-size:11pt;text-align:justify;margin-bottom:16pt;">
  Il a été convenu et arrêté ce qui suit entre les parties désignées ci-dessus :
</p>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 1 — Engagement et Période d'Essai</p>
  <p style="text-align:justify;">
    {{company_name}} engage {{employee_name}} à compter du {{start_date}} pour {{contract_duration}}.
    Le présent contrat est conclu pour une durée indéterminée.
    Les deux parties conviennent d'une période d'essai de <strong>{{probation_period}} mois</strong>,
    durant laquelle chacune des parties peut mettre fin au contrat sans préavis ni indemnité.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 2 — Textes Régissant le Contrat</p>
  <p style="text-align:justify;">
    Le présent contrat est soumis aux dispositions de la loi n° 2004/017 du 6 juillet 2004 portant
    Code du Travail de la République Islamique de Mauritanie, ainsi qu'à la Convention Collective
    Nationale du Travail signée le 15 janvier 1974 et à ses avenants.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 3 — Durée du Contrat</p>
  <p style="text-align:justify;">
    Ce contrat est conclu pour {{contract_duration}}, à compter du <strong>{{start_date}}</strong>.
    Il ne pourra être rompu qu'en respectant les modalités prévues par la législation du travail en vigueur.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 4 — Emploi et Classification</p>
  <p style="text-align:justify;">
    Le salarié est engagé en qualité de <strong>{{job_title}}</strong>, classification <strong>{{category}}</strong>.
    Il exercera ses fonctions au sein de {{company_name}} et sera placé sous l'autorité de sa hiérarchie directe.
    Le lieu de travail principal est fixé à <strong>{{work_location}}</strong>.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 5 — Salaire et Indemnités</p>
  <p style="text-align:justify;">
    En contrepartie de ses services, le salarié percevra une rémunération mensuelle brute de
    <strong>{{base_salary}}</strong>, versée à terme échu conformément aux usages en vigueur.
    La durée de travail est fixée à <strong>{{working_hours}}/jour</strong>, soit
    <strong>{{working_days}}/semaine</strong>.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 6 — Congé Annuel</p>
  <p style="text-align:justify;">
    Le salarié a droit à un congé annuel payé conformément aux dispositions légales en vigueur en Mauritanie.
    La durée et les modalités de prise de ce congé sont définies d'un commun accord entre l'employeur et le salarié,
    dans le respect des impératifs d'organisation de l'entreprise.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 7 — Lieu d'Emploi</p>
  <p style="text-align:justify;">
    La mission du salarié s'exercera principalement à <strong>{{work_location}}</strong>.
    Toutefois, l'employeur se réserve le droit de muter le salarié dans tout autre établissement ou lieu de travail
    en Mauritanie en fonction des nécessités du service, après notification préalable.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 8 — Obligations du Salarié</p>
  <p style="text-align:justify;">
    Le salarié s'engage à consacrer l'intégralité de son temps de travail aux tâches qui lui sont confiées,
    à respecter le règlement intérieur de l'entreprise, les procédures et les directives de sa hiérarchie,
    et à s'abstenir de tout acte préjudiciable aux intérêts de la société.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 9 — Clause de Confidentialité</p>
  <p style="text-align:justify;">
    Le salarié s'engage à garder le secret le plus absolu sur les informations confidentielles dont il aura
    connaissance dans l'exercice de ses fonctions. Cette obligation de confidentialité perdurera pendant
    une durée de deux (2) ans après la cessation du présent contrat, quelle qu'en soit la cause.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 10 — Non-Concurrence</p>
  <p style="text-align:justify;">
    Pendant la durée du contrat et pour une période de douze (12) mois après sa cessation,
    le salarié s'engage à ne pas exercer, directement ou indirectement, une activité concurrente
    à celle de {{company_name}} sur le territoire mauritanien, sans accord écrit préalable de l'employeur.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 11 — Rupture du Contrat</p>
  <p style="text-align:justify;">
    En cas de rupture du contrat à l'initiative de l'une ou l'autre des parties, un préavis de
    <strong>{{notice_period}}</strong> devra être respecté, sauf en cas de faute grave ou lourde.
    Les indemnités de rupture seront calculées conformément aux dispositions du Code du Travail mauritanien.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 12 — Litiges</p>
  <p style="text-align:justify;">
    En cas de litige relatif à l'exécution ou à la rupture du présent contrat, les parties s'efforceront
    de le résoudre à l'amiable. À défaut, le litige sera soumis aux juridictions compétentes de Nouakchott,
    conformément aux dispositions de la législation mauritanienne en vigueur.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 13 — Clause Particulière</p>
  <p style="text-align:justify;">
    Les parties reconnaissent que le présent contrat constitue l'intégralité de leur accord
    et remplace tout accord ou arrangement antérieur relatif à l'emploi du salarié au sein de {{company_name}}.
    Toute modification du présent contrat devra faire l'objet d'un avenant signé par les deux parties.
  </p>
</div>

<div style="margin-top:32pt;page-break-inside:avoid;">
  <p style="font-size:10pt;color:#5c5c5a;margin-bottom:16pt;">
    Fait à {{work_location}}, le {{today_date}}, en deux (2) exemplaires originaux, dont un remis à chaque partie.
  </p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="width:50%;vertical-align:top;padding-right:16pt;">
        <p style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Pour l'Employeur</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{company_name}}</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{representative_name}}, {{representative_title}}</p>
        <div style="margin-top:40pt;border-bottom:1px solid #8c8c8a;width:80%;"></div>
        <p style="font-size:9pt;color:#8c8c8a;margin-top:4pt;">Signature et cachet</p>
      </td>
      <td style="width:50%;vertical-align:top;padding-left:16pt;">
        <p style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Le Salarié</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{employee_name}}</p>
        <p style="font-size:10pt;color:#5c5c5a;">Lu et approuvé</p>
        <div style="margin-top:40pt;border-bottom:1px solid #8c8c8a;width:80%;"></div>
        <p style="font-size:9pt;color:#8c8c8a;margin-top:4pt;">Signature</p>
      </td>
    </tr>
  </table>
</div>

<div style="position:fixed;bottom:0;left:0;right:0;padding:6pt 18mm;border-top:1px solid #eeeee9;background:#faf9f5;">
  <table style="width:100%;font-size:8pt;color:#8c8c8a;">
    <tr>
      <td>{{company_name}} — Contrat de Travail CDI</td>
      <td style="text-align:center;">Réf. : {{contract_reference}}</td>
      <td style="text-align:right;">Généré le {{today_date}}</td>
    </tr>
  </table>
</div>
CONTENT;
    }

    private function cddContent(): string
    {
        return <<<'CONTENT'
<div style="text-align:center;margin-bottom:24pt;">
  <p style="font-size:10pt;color:#5c5c5a;margin-bottom:4pt;">{{company_name}} — {{company_address}}</p>
  <h1 style="font-size:16pt;font-weight:700;letter-spacing:1pt;margin:0">CONTRAT DE TRAVAIL</h1>
  <h2 style="font-size:13pt;font-weight:600;margin:4pt 0 0 0">À DURÉE DÉTERMINÉE</h2>
  <p style="font-size:10pt;color:#5c5c5a;margin-top:6pt;">Réf. : {{contract_reference}}</p>
</div>

<table style="width:100%;margin-bottom:20pt;border:1px solid #e5e4df;border-collapse:collapse;">
  <tr>
    <td style="width:50%;padding:10pt;border:1px solid #e5e4df;vertical-align:top;">
      <p style="font-size:9pt;font-weight:700;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6pt;">L'EMPLOYEUR</p>
      <p style="margin:0;font-weight:700;">{{company_name}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">{{company_address}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Immatriculation : {{company_registration}}</p>
      <p style="margin:6pt 0 0 0;font-size:10pt;">Représentée par <strong>{{representative_name}}</strong>, {{representative_title}}</p>
    </td>
    <td style="width:50%;padding:10pt;border:1px solid #e5e4df;vertical-align:top;">
      <p style="font-size:9pt;font-weight:700;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6pt;">LE SALARIÉ</p>
      <p style="margin:0;font-weight:700;">{{employee_name}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">NNI : {{employee_nni}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Date de naissance : {{employee_dob}}</p>
      <p style="margin:2pt 0;font-size:10pt;color:#5c5c5a;">Nationalité : {{employee_nationality}}</p>
    </td>
  </tr>
</table>

<p style="font-size:11pt;text-align:justify;margin-bottom:16pt;">
  Il a été convenu et arrêté ce qui suit entre les parties désignées ci-dessus :
</p>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 1 — Engagement et Période d'Essai</p>
  <p style="text-align:justify;">
    {{company_name}} engage {{employee_name}} à compter du {{start_date}} pour {{contract_duration}}.
    Le présent contrat est conclu pour une durée déterminée et prendra fin au <strong>{{end_date}}</strong>,
    sauf renouvellement exprès ou rupture anticipée dans les conditions prévues par la loi.
    Les deux parties conviennent d'une période d'essai de <strong>{{probation_period}} mois</strong>,
    durant laquelle chacune des parties peut mettre fin au contrat sans préavis ni indemnité.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 2 — Textes Régissant le Contrat</p>
  <p style="text-align:justify;">
    Le présent contrat est soumis aux dispositions de la loi n° 2004/017 du 6 juillet 2004 portant
    Code du Travail de la République Islamique de Mauritanie, ainsi qu'à la Convention Collective
    Nationale du Travail signée le 15 janvier 1974 et à ses avenants.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 3 — Durée, Terme et Renouvellement</p>
  <p style="text-align:justify;">
    Ce contrat est conclu pour une durée déterminée allant du <strong>{{start_date}}</strong> au
    <strong>{{end_date}}</strong>. Il arrive à son terme de plein droit à la date prévue, sans formalité particulière,
    sous réserve des dispositions légales applicables. Tout renouvellement devra faire l'objet d'un avenant écrit
    signé par les deux parties avant l'échéance.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 4 — Emploi et Classification</p>
  <p style="text-align:justify;">
    Le salarié est engagé en qualité de <strong>{{job_title}}</strong>, classification <strong>{{category}}</strong>.
    Il exercera ses fonctions au sein de {{company_name}} et sera placé sous l'autorité de sa hiérarchie directe.
    Le lieu de travail principal est fixé à <strong>{{work_location}}</strong>.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 5 — Salaire et Indemnités</p>
  <p style="text-align:justify;">
    En contrepartie de ses services, le salarié percevra une rémunération mensuelle brute de
    <strong>{{base_salary}}</strong>, versée à terme échu conformément aux usages en vigueur.
    La durée de travail est fixée à <strong>{{working_hours}}/jour</strong>, soit
    <strong>{{working_days}}/semaine</strong>.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 6 — Congé Annuel</p>
  <p style="text-align:justify;">
    Le salarié a droit à un congé annuel payé conformément aux dispositions légales en vigueur en Mauritanie.
    La durée et les modalités de prise de ce congé sont définies d'un commun accord entre l'employeur et le salarié,
    dans le respect des impératifs d'organisation de l'entreprise.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 7 — Lieu d'Emploi</p>
  <p style="text-align:justify;">
    La mission du salarié s'exercera principalement à <strong>{{work_location}}</strong>.
    Toutefois, l'employeur se réserve le droit de muter le salarié dans tout autre établissement ou lieu de travail
    en Mauritanie en fonction des nécessités du service, après notification préalable.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 8 — Obligations du Salarié</p>
  <p style="text-align:justify;">
    Le salarié s'engage à consacrer l'intégralité de son temps de travail aux tâches qui lui sont confiées,
    à respecter le règlement intérieur de l'entreprise, les procédures et les directives de sa hiérarchie,
    et à s'abstenir de tout acte préjudiciable aux intérêts de la société.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 9 — Clause de Confidentialité</p>
  <p style="text-align:justify;">
    Le salarié s'engage à garder le secret le plus absolu sur les informations confidentielles dont il aura
    connaissance dans l'exercice de ses fonctions. Cette obligation de confidentialité perdurera pendant
    une durée de deux (2) ans après la cessation du présent contrat, quelle qu'en soit la cause.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 10 — Non-Concurrence</p>
  <p style="text-align:justify;">
    Pendant la durée du contrat et pour une période de douze (12) mois après sa cessation,
    le salarié s'engage à ne pas exercer, directement ou indirectement, une activité concurrente
    à celle de {{company_name}} sur le territoire mauritanien, sans accord écrit préalable de l'employeur.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 11 — Fin du Contrat et Rupture Anticipée</p>
  <p style="text-align:justify;">
    Le présent CDD prend fin automatiquement à son terme, soit le <strong>{{end_date}}</strong>, sauf renouvellement écrit.
    Toute rupture anticipée ne peut intervenir que dans les cas prévus par la législation du travail en vigueur,
    notamment en cas d'accord des parties, de faute grave ou de force majeure.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 12 — Litiges</p>
  <p style="text-align:justify;">
    En cas de litige relatif à l'exécution ou à la rupture du présent contrat, les parties s'efforceront
    de le résoudre à l'amiable. À défaut, le litige sera soumis aux juridictions compétentes de Nouakchott,
    conformément aux dispositions de la législation mauritanienne en vigueur.
  </p>
</div>

<div class="article" style="margin:12pt 0;">
  <p class="article-title" style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Article 13 — Clause Particulière</p>
  <p style="text-align:justify;">
    Les parties reconnaissent que le présent contrat constitue l'intégralité de leur accord
    et remplace tout accord ou arrangement antérieur relatif à l'emploi du salarié au sein de {{company_name}}.
    Toute modification du présent contrat devra faire l'objet d'un avenant signé par les deux parties.
  </p>
</div>

<div style="margin-top:32pt;page-break-inside:avoid;">
  <p style="font-size:10pt;color:#5c5c5a;margin-bottom:16pt;">
    Fait à {{work_location}}, le {{today_date}}, en deux (2) exemplaires originaux, dont un remis à chaque partie.
  </p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="width:50%;vertical-align:top;padding-right:16pt;">
        <p style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Pour l'Employeur</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{company_name}}</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{representative_name}}, {{representative_title}}</p>
        <div style="margin-top:40pt;border-bottom:1px solid #8c8c8a;width:80%;"></div>
        <p style="font-size:9pt;color:#8c8c8a;margin-top:4pt;">Signature et cachet</p>
      </td>
      <td style="width:50%;vertical-align:top;padding-left:16pt;">
        <p style="font-weight:700;font-size:11pt;margin-bottom:4pt;">Le Salarié</p>
        <p style="font-size:10pt;color:#5c5c5a;">{{employee_name}}</p>
        <p style="font-size:10pt;color:#5c5c5a;">Lu et approuvé</p>
        <div style="margin-top:40pt;border-bottom:1px solid #8c8c8a;width:80%;"></div>
        <p style="font-size:9pt;color:#8c8c8a;margin-top:4pt;">Signature</p>
      </td>
    </tr>
  </table>
</div>

<div style="position:fixed;bottom:0;left:0;right:0;padding:6pt 18mm;border-top:1px solid #eeeee9;background:#faf9f5;">
  <table style="width:100%;font-size:8pt;color:#8c8c8a;">
    <tr>
      <td>{{company_name}} — Contrat de Travail CDD</td>
      <td style="text-align:center;">Réf. : {{contract_reference}}</td>
      <td style="text-align:right;">Généré le {{today_date}}</td>
    </tr>
  </table>
</div>
CONTENT;
    }
}
