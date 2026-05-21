<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #fff;
  }

  #top-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #1a1a1a;
    font-size: 1px;
    line-height: 1px;
  }

  .page-body {
    padding: 52px 56px 80px 56px;
  }

  /* Header */
  .header {
    width: 100%;
    margin-bottom: 36px;
  }
  .header-left { width: 55%; vertical-align: top; }
  .header-right { width: 45%; vertical-align: top; text-align: right; }

  .company-meta { font-size: 9px; color: #aaa; line-height: 2; margin-top: 8px; }

  .invoice-title {
    font-size: 22px;
    font-weight: normal;
    color: #1a1a1a;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .invoice-number {
    font-size: 9px;
    color: #bbb;
    margin-bottom: 12px;
    letter-spacing: 0.04em;
  }
  .meta-line { font-size: 9px; color: #888; line-height: 2.1; }
  .meta-lbl {
    font-size: 7.5px;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #ccc;
    margin-right: 8px;
  }

  /* Status */
  .inv-status {
    font-size: 8.5px;
    font-weight: bold;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-align: right;
    margin-top: 11px;
  }
  .inv-status.overdue  { color: #c0392b; }
  .inv-status.partial  { color: #e67e22; }
  .inv-status.paid     { color: #27ae60; }
  .inv-status-sub {
    font-size: 8px;
    color: #e67e22;
    text-align: right;
    margin-top: 3px;
  }

  /* Divider */
  .divider { border: none; border-top: 1px solid #ececec; margin: 0 0 28px 0; }

  /* Bill To */
  .bill-table { width: 100%; margin-bottom: 32px; }
  .section-label {
    font-size: 7.5px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #ccc;
    margin-bottom: 8px;
  }
  .customer-name    { font-size: 14px; color: #1a1a1a; margin-bottom: 5px; }
  .customer-details { font-size: 10px; color: #999; line-height: 1.9; }
  .project-name     { font-size: 11px; color: #1a1a1a; }

  /* Items table */
  .items-table { width: 100%; border-collapse: collapse; }
  .items-table th {
    font-size: 7.5px;
    font-weight: normal;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #bbb;
    padding: 0 8px 10px 0;
    text-align: left;
    border-bottom: 1.5px solid #1a1a1a;
  }
  .items-table th.r { text-align: right; padding: 0 0 10px 8px; }
  .items-table td {
    font-size: 10.5px;
    color: #1a1a1a;
    padding: 13px 8px 13px 0;
    border-bottom: 1px solid #f3f3f3;
    vertical-align: top;
  }
  .items-table td.r     { text-align: right; padding: 13px 0 13px 8px; }
  .items-table td.muted { color: #aaa; }
  .items-table td.desc  { white-space: normal; word-wrap: break-word; line-height: 1.6; }
  .task-tag { font-size: 7.5px; color: #ccc; margin-left: 4px; }

  /* Totals */
  .totals-table { width: 260px; border-collapse: collapse; margin-left: auto; margin-top: 20px; margin-bottom: 28px; }
  .totals-table td { font-size: 10.5px; padding: 4px 0; }
  .totals-table td.lbl { color: #aaa; }
  .totals-table td.r   { text-align: right; }
  .total-row td { padding-top: 13px; border-top: 1.5px solid #1a1a1a; }
  .total-lbl { font-size: 11px; color: #1a1a1a; }
  .total-val { font-size: 15px; font-weight: bold; text-align: right; color: #1a1a1a; }
  .due-red   { font-size: 13px; font-weight: bold; text-align: right; color: #c0392b; }
  .due-ok    { font-size: 13px; font-weight: bold; text-align: right; color: #27ae60; }

  /* Payments */
  .payments-section { margin-bottom: 24px; margin-top: 4px; }
  .payments-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  .payments-table td { font-size: 9.5px; color: #888; padding: 7px 0; border-bottom: 1px solid #f5f5f5; }
  .payments-table td.r { text-align: right; color: #1a1a1a; }

  /* Notes */
  .notes-section { padding-top: 20px; border-top: 1px solid #ececec; margin-top: 28px; }
  .notes-text { font-size: 9.5px; color: #aaa; line-height: 1.85; }

  /* Footer */
  #footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #f4f4f4;
    border-top: 1px solid #d8d8d8;
    padding: 12px 56px;
    text-align: center;
    font-size: 9px;
    color: #888;
    letter-spacing: 0.05em;
  }
</style>
</head>
<body>

<div id="footer">
  {{ config('app.name', 'PiShift') }}
  &middot; NIF: 01653526
  &middot; support@pishift.co
</div>

<div id="top-bar"></div>

<div class="page-body">

@php
  $amountDue    = max(0, $invoice->total - $invoice->amount_paid);
  $hasDiscount  = $invoice->items->contains(fn($i) => ($i->discount_value ?? 0) > 0);
  $hasTaxOrDisc = ($invoice->discount_amount ?? 0) > 0 || ($invoice->tax_amount ?? 0) > 0;
  $isOverdue    = $amountDue > 0 && $invoice->due_date->isPast();
@endphp

<table class="header" cellpadding="0" cellspacing="0">
<tr>
  <td class="header-left">
    @if(!empty($logoPath))
      <img src="{{ $logoPath }}" style="height:36px;width:auto;display:block;">
    @else
      <div style="font-size:18px;font-weight:bold;color:#1a1a1a;">{{ config('app.name', 'PiShift') }}</div>
    @endif
    <div class="company-meta">
      @if(config('app.email'))  {{ config('app.email') }}<br> @endif
      @if(config('app.phone'))  {{ config('app.phone') }} @endif
    </div>
  </td>
  <td class="header-right">
    <div class="invoice-title">Invoice</div>
    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
    <div class="meta-line">
      <span class="meta-lbl">Issued</span>{{ $invoice->issue_date->format('M d, Y') }}<br>
      <span class="meta-lbl">Due</span>{{ $invoice->due_date->format('M d, Y') }}
    </div>

    
    @if($amountDue <= 0)
      <div class="inv-status paid">Paid</div>
    @elseif($invoice->amount_paid > 0)
      <div class="inv-status partial">Partially paid</div>
      <div class="inv-status-sub">
        Paid: {{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}
        &middot;
        Due: {{ $invoice->currency }} {{ number_format($amountDue, 2) }}
      </div>
    @elseif($isOverdue)
      <div class="inv-status overdue">Overdue</div>
    @endif
    

  </td>
</tr>
</table>

<hr class="divider">

<table class="bill-table" cellpadding="0" cellspacing="0">
<tr>
  <td style="width:55%;vertical-align:top;">
    <div class="section-label">Bill to</div>
    <div class="customer-name">{{ $invoice->customer->name }}</div>
    <div class="customer-details">
      @if($invoice->customer->company ?? null) {{ $invoice->customer->company }}<br> @endif
      @if($invoice->customer->email   ?? null) {{ $invoice->customer->email   }}<br> @endif
      @if($invoice->customer->phone   ?? null) {{ $invoice->customer->phone   }}     @endif
    </div>
  </td>
  @if($invoice->project)
  <td style="width:45%;vertical-align:top;text-align:right;">
    <div class="section-label" style="text-align:right;">Project</div>
    <div class="project-name">{{ $invoice->project->name }}</div>
  </td>
  @endif
</tr>
</table>

<table class="items-table">
  <thead>
    <tr>
      <th style="width:4%">#</th>
      <th style="width:{{ $hasDiscount ? '38%' : '44%' }}">Description</th>
      <th style="width:9%">Unit</th>
      <th class="r" style="width:7%">Qty</th>
      <th class="r" style="width:{{ $hasDiscount ? '13%' : '17%' }}">Unit price</th>
      @if($hasDiscount) <th class="r" style="width:10%">Discount</th> @endif
      <th class="r" style="width:{{ $hasDiscount ? '15%' : '19%' }}">Subtotal</th>
    </tr>
  </thead>
  <tbody>
  @foreach($invoice->items->sortBy('sort_order') as $idx => $item)
    <tr>
      <td class="muted">{{ $idx + 1 }}</td>
      <td class="desc" style="line-height:1.6">
        @php
            $descLines = explode("\n", trim($item->description ?? ''));
            $firstNonEmpty = true;
        @endphp
        @foreach($descLines as $descLine)
            @php $t = rtrim($descLine); @endphp
            @if($t === '')
                <br>
            @elseif($firstNonEmpty)
                @php $firstNonEmpty = false; @endphp
                <strong style="font-size:11px;font-weight:bold;color:#1a1a1a">{{ $t }}</strong>
            @elseif(str_starts_with($t, '·') || str_starts_with($t, '-'))
                <table style="width:100%;border-collapse:collapse;margin:0;padding:0;font-size:9.5px;color:#666666"><tr>
                  <td style="width:12px;vertical-align:top;padding:0">{{ mb_substr($t, 0, 1) }}</td>
                  <td style="vertical-align:top;padding:0">{{ trim(mb_substr($t, 1)) }}</td>
                </tr></table>
            @else
                <div style="font-size:9.5px;color:#666666">{{ $t }}</div>
            @endif
        @endforeach
      </td>
      <td class="muted">{{ $item->unit }}</td>
      <td class="r muted">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
      <td class="r">{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
      @if($hasDiscount)
      <td class="r muted">
        @php $dv = $item->discount_value ?? 0; @endphp
        @if($item->discount_type === 'percent' && $dv > 0) {{ $dv }}%
        @elseif($item->discount_type === 'fixed' && $dv > 0) {{ $invoice->currency }} {{ number_format($dv, 2) }}
        @else -
        @endif
      </td>
      @endif
      <td class="r">{{ $invoice->currency }} {{ number_format($item->subtotal, 2) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<table class="totals-table">
  @if($hasTaxOrDisc)
  <tr>
    <td class="lbl">Subtotal</td>
    <td class="r">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
  </tr>
  @if(($invoice->discount_amount ?? 0) > 0)
  <tr>
    <td class="lbl">{{ $invoice->discount_type === 'percent' ? 'Discount ('.$invoice->discount_value.'%)' : 'Discount' }}</td>
    <td class="r lbl">&minus; {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
  </tr>
  @endif
  @if(($invoice->tax_amount ?? 0) > 0)
  <tr>
    <td class="lbl">Tax ({{ $invoice->tax_rate }}%)</td>
    <td class="r lbl">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
  </tr>
  @endif
  @endif
  <tr class="total-row">
    <td class="total-lbl">Total</td>
    <td class="total-val">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
  </tr>
  @if($invoice->amount_paid > 0)
  <tr>
    <td class="lbl" style="padding-top:6px">Amount paid</td>
    <td class="r lbl" style="padding-top:6px">&minus; {{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td>
  </tr>
  @endif
  <tr>
    <td class="lbl" style="padding-top:5px">Amount due</td>
    <td class="{{ $amountDue > 0 ? 'due-red' : 'due-ok' }}" style="padding-top:5px">
      {{ $invoice->currency }} {{ number_format($amountDue, 2) }}
    </td>
  </tr>
</table>

@if($invoice->payments && $invoice->payments->isNotEmpty())
<div class="payments-section">
  <div class="section-label">Recorded payments</div>
  <table class="payments-table">
    @foreach($invoice->payments as $payment)
    <tr>
      <td>{{ $payment->payment_date->format('M d, Y') }}</td>
      <td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td>
      <td style="color:#ccc">{{ $payment->reference ?? '' }}</td>
      <td class="r">{{ $invoice->currency }} {{ number_format($payment->amount, 2) }}</td>
    </tr>
    @endforeach
  </table>
</div>
@endif

@if($invoice->notes)
<div class="notes-section">
  <div class="notes-text">{{ $invoice->notes }}</div>
</div>
@endif

</div>

<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");
        $pdf->text(397, 831, ($PAGE_NUM) . " / " . ($PAGE_COUNT), $font, 7, [0.6, 0.6, 0.6]);
    ');
}
</script>

</body>
</html>