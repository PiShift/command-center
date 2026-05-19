@extends('emails.layout')

@section('content')
    <h1>Credit applied to your invoice</h1>
    <p>Dear {{ $invoice->customer->name }},</p>
    <p>A credit of <strong>{{ $invoice->currency }} {{ number_format($creditAmount, 2) }}</strong> has been applied to invoice <strong>{{ $invoice->invoice_number }}</strong>.</p>
    <div class="meta">
        <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Credit Applied:</strong> {{ $invoice->currency }} {{ number_format($creditAmount, 2) }}</p>
        <p><strong>New Balance:</strong> {{ $invoice->currency }} {{ number_format(max(0, $invoice->total - $invoice->amount_paid), 2) }}</p>
    </div>
@endsection
