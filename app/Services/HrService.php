<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\EmployeeContract;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class HrService
{
    /**
     * Legacy method — renders the hardcoded Blade template.
     * Kept for backwards compatibility.
     */
    public function generateContract(EmployeeContract $contract): Response
    {
        $contract->load('employee.user', 'template');

        // If a template is linked or a default exists, use the new system
        $template = $contract->template
            ?? ContractTemplate::getDefaultForType($contract->employment_type);

        if ($template) {
            $dompdf  = $this->generateContractPdf($contract);
            $employee = $contract->employee;
            $filename = 'contract-' . $employee->employee_number . '-' . $contract->effective_from->format('Y-m') . '.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        // Fall back to hardcoded Blade view
        $pdf = Pdf::loadView('hr.contract-pdf', compact('contract'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'    => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont'        => 'DejaVu Sans',
            ]);

        $employee = $contract->employee;
        $filename = 'contract-' . $employee->employee_number . '-' . $contract->effective_from->format('Y-m') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Generate a contract PDF from a template, returning the Dompdf instance.
     */
    public function generateContractPdf(EmployeeContract $contract): Dompdf
    {
        $contract->loadMissing('employee.user', 'template');

        $template = $contract->template
            ?? ContractTemplate::getDefaultForType($contract->employment_type);

        if (! $template) {
            // Build a minimal fallback HTML from the Blade view and render it
            $html = view('hr.contract-pdf', compact('contract'))->render();
        } else {
            $replacements = $this->buildReplacementMap($contract);

            // Also replace <code>{{placeholder}}</code> (inserted via the UI button)
            // alongside plain {{placeholder}} (typed manually), so both forms work in PDFs.
            foreach (array_keys($replacements) as $placeholder) {
                $replacements['<code>' . $placeholder . '</code>'] = $replacements[$placeholder];
            }

            $body         = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $template->content
            );

            $html = $this->wrapInHtmlShell($body, $template->language);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildReplacementMap(EmployeeContract $contract): array
    {
        $employee = $contract->employee;

        return [
            '{{company_name}}'         => config('app.company_name', 'PiShift'),
            '{{company_address}}'      => config('app.company_address', 'Nouakchott, Mauritanie'),
            '{{company_phone}}'        => config('app.phone', ''),
            '{{company_email}}'        => config('app.email', ''),
            '{{company_registration}}' => config('app.company_registration', ''),
            '{{representative_name}}'  => config('app.representative_name', ''),
            '{{representative_title}}' => config('app.representative_title', 'Direction Générale'),
            '{{employee_name}}'        => $employee->user->name,
            '{{employee_nni}}'         => $employee->nni ?? '—',
            '{{employee_dob}}'         => $employee->date_of_birth?->format('d/m/Y') ?? '—',
            '{{employee_nationality}}' => $employee->nationality ?? 'Mauritanienne',
            '{{contract_type_label}}'  => strtoupper($contract->employment_type),
            '{{job_title}}'            => $employee->job_title ?? '—',
            '{{category}}'            => $employee->category ?? '—',
            '{{base_salary}}'          => number_format((float) $contract->base_salary, 2) . ' MRU',
            '{{start_date}}'           => $contract->effective_from->format('d/m/Y'),
            '{{end_date}}'             => $contract->effective_to?->format('d/m/Y') ?? 'Indéterminée',
            '{{probation_period}}'     => (string) ($employee->probation_period_months ?? 2),
            '{{notice_period}}'        => $contract->notice_period_days . ' jours',
            '{{work_location}}'        => $employee->work_location ?? 'Nouakchott',
            '{{working_hours}}'        => $contract->working_hours_per_day . 'h',
            '{{working_days}}'         => $contract->working_days_per_week . ' jours',
            '{{today_date}}'           => now()->format('d/m/Y'),
            '{{contract_reference}}'   => $contract->contract_reference ?? '—',
            '{{contract_duration}}'    => $contract->employment_type === 'CDI'
                ? 'une durée indéterminée'
                : 'une durée déterminée jusqu\'au ' . ($contract->effective_to?->format('d/m/Y') ?? '—'),
        ];
    }

    private function wrapInHtmlShell(string $body, string $language = 'fr'): string
    {
        $dir = $language === 'ar' ? 'rtl' : 'ltr';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$language}" dir="{$dir}">
<head>
<meta charset="UTF-8">
<style>
  @font-face { font-family: 'DejaVu Sans'; }
  * { font-family: 'DejaVu Sans', sans-serif; box-sizing: border-box; }
  body {
    font-size: 11pt;
    line-height: 1.6;
    color: #141413;
    margin: 0;
    padding: 0;
  }
  @page {
    margin: 22mm 18mm 25mm 18mm;
    size: A4;
  }
  p { margin: 0 0 8pt 0; }
  h1 { font-size: 15pt; font-weight: 700; margin: 0 0 6pt 0; }
  h2 { font-size: 12pt; font-weight: 700; margin: 12pt 0 4pt 0; }
  table { width: 100%; border-collapse: collapse; }
  td, th { padding: 5pt 8pt; vertical-align: top; }
  .article { margin: 10pt 0; }
  .article-title { font-weight: 700; font-size: 11pt; margin-bottom: 4pt; }
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
}

