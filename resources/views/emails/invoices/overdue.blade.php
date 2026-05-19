@extends('emails.layout')

@section('content')
    <h1>Payment overdue</h1>
    <p>Dear {{ $invoice->customer->name }},</p>
    <p>This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> is past its due date.</p>
    <div class="meta">
        <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
        <p><strong>Outstanding Amount:</strong> {{ $invoice->currency }} {{ number_format($invoice->total - $invoice->amount_paid, 2) }}</p>
    </div>
    <p>Please settle this payment as soon as possible. If you have already made payment, please disregard this notice.</p>
@endsection
