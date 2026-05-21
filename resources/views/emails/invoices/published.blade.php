@extends('emails.layout')

@section('content')
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
    <p>Dear {{ $invoice->customer->name }},</p>
    <p>Please find below the details of your invoice.</p>
    <div class="meta">
        <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</p>
        <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
        <p><strong>Currency:</strong> {{ $invoice->currency }}</p>
    </div>
    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <thead>
            <tr style="background:#F5F4EF">
                <th style="text-align:left;padding:8px 12px;font-size:13px;color:#141413">Description</th>
                <th style="text-align:right;padding:8px 12px;font-size:13px;color:#141413">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr style="border-bottom:1px solid #e5e4df">
                <td style="padding:8px 12px;font-size:13px;color:#5c5c5a">@include('invoices._item_description', ['description' => $item->description])</td>
                <td style="text-align:right;padding:8px 12px;font-size:13px;color:#141413">{{ $invoice->currency }} {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td style="padding:8px 12px;font-weight:700;color:#141413">Total</td>
                <td style="text-align:right;padding:8px 12px;font-weight:700;color:#141413">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
            </tr>
            @if($invoice->amount_due > 0)
            <tr>
                <td style="padding:8px 12px;color:#c0392b;font-weight:700;">Amount Due</td>
                <td style="text-align:right;padding:8px 12px;font-weight:700;color:#c0392b;">{{ $invoice->currency }} {{ number_format($invoice->amount_due, 2) }}</td>
            </tr>
            @else
            <tr>
                <td style="padding:8px 12px;color:#27ae60;font-weight:700;">PAID</td>
                <td style="text-align:right;padding:8px 12px;font-weight:700;color:#27ae60;">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>
    <p style="font-size:13px;color:#8c8c8a">Please make your payment before {{ $invoice->due_date->format('M d, Y') }}. Thank you for your business.</p>
@endsection
