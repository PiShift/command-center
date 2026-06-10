<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payroll {{ $run->month->format('F Y') }}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
  .page { padding: 34px 42px; }
  .header { width: 100%; margin-bottom: 20px; }
  .title { font-size: 20px; font-weight: 700; margin-top: 10px; }
  .meta { font-size: 10px; color: #666; margin-top: 4px; }
  .summary { width: 100%; margin: 16px 0; border-collapse: collapse; }
  .summary td { border: 1px solid #e5e5e5; padding: 8px; }
  .summary .lbl { font-size: 9px; text-transform: uppercase; color: #666; letter-spacing: 0.08em; }
  .summary .val { font-size: 12px; font-weight: 700; }
  table.entries { width: 100%; border-collapse: collapse; margin-top: 12px; }
  table.entries th { background: #f5f5f5; border: 1px solid #e5e5e5; padding: 7px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #666; text-align: left; }
  table.entries th.r, table.entries td.r { text-align: right; }
  table.entries td { border: 1px solid #eeeeee; padding: 7px 6px; font-size: 10px; vertical-align: top; }
  .total-row td { font-weight: 700; border-top: 1.5px solid #777; }
  .signatures { width: 100%; margin-top: 34px; }
  .sign { width: 45%; display: inline-block; vertical-align: top; }
  .line { border-top: 1px solid #999; margin-top: 30px; padding-top: 6px; font-size: 10px; color: #666; }
</style>
</head>
<body>
<div class="page">
    <table class="header">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if(!empty($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" style="height: 34px; width: auto;">
                @endif
                <div class="title">{{ $run->month->format('F Y') }} Payroll</div>
                <div class="meta">Status: {{ ucfirst($run->status) }} @if($run->paid_at) · Paid {{ $run->paid_at->format('M d, Y H:i') }} @endif</div>
            </td>
            <td style="width: 45%; text-align: right; vertical-align: top;">
                <div class="meta">Generated {{ now()->format('M d, Y H:i') }}</div>
                <div class="meta">Prepared by {{ $run->creator?->name ?? 'System' }}</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="lbl">Total Gross</div>
                <div class="val">MRU {{ number_format((float) $run->total_gross, 2) }}</div>
            </td>
            <td>
                <div class="lbl">Total Deductions</div>
                <div class="val">MRU {{ number_format((float) $run->total_deductions, 2) }}</div>
            </td>
            <td>
                <div class="lbl">Total Net</div>
                <div class="val">MRU {{ number_format((float) $run->total_net, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="entries">
        <thead>
            <tr>
                <th>Employee</th>
                <th class="r">Base Salary</th>
                <th class="r">Bonuses</th>
                <th class="r">Advances</th>
                <th class="r">Loans</th>
                <th class="r">Other Deductions</th>
                <th class="r">Net Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($run->entries as $entry)
                <tr>
                    <td>{{ $entry->employee?->display_name ?? 'Unknown' }} ({{ $entry->employee?->employee_number ?? '—' }})</td>
                    <td class="r">{{ number_format((float) $entry->base_salary, 2) }}</td>
                    <td class="r">{{ number_format((float) $entry->bonuses, 2) }}</td>
                    <td class="r">{{ number_format((float) $entry->advances_deducted, 2) }}</td>
                    <td class="r">{{ number_format((float) $entry->loans_deducted, 2) }}</td>
                    <td class="r">{{ number_format((float) $entry->other_deductions, 2) }}</td>
                    <td class="r">{{ number_format((float) $entry->net_amount, 2) }}</td>
                    <td>{{ ucfirst($entry->status) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td class="r">{{ number_format((float) $run->total_gross, 2) }}</td>
                <td class="r">—</td>
                <td class="r">—</td>
                <td class="r">—</td>
                <td class="r">{{ number_format((float) $run->total_deductions, 2) }}</td>
                <td class="r">{{ number_format((float) $run->total_net, 2) }}</td>
                <td>—</td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="sign">
            <div class="line">Prepared by</div>
        </div>
        <div class="sign" style="float: right;">
            <div class="line">Approved by</div>
        </div>
    </div>
</div>
</body>
</html>
