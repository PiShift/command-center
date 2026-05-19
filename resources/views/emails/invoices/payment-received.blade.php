@extends('emails.layout')

@section('content')
    <h1>Payment received</h1>
    <p>Dear {{ $invoice->customer->name }},</p>
    <p>We have received your payment for invoice <strong>{{ $invoice->invoice_number }}</strong>. Thank you!</p>
    <div class="meta">
        <p><strong>Amount paid:</strong> {{ $invoice->currency }} {{ number_format($payment->amount, 2) }}</p>
        <p><strong>Payment date:</strong> {{ $payment->payment_date->format('M d, Y') }}</p>
        <p><strong>Payment method:</strong> {{ str_replace('_', ' ', ucfirst($payment->method)) }}</p>
        @if($invoice->amount_paid < $invoice->total)
            <p><strong>Remaining balance:</strong> {{ $invoice->currency }} {{ number_format($invoice->total - $invoice->amount_paid, 2) }}</p>
        @else
            <p><strong>Status:</strong> Fully paid ✓</p>
        @endif
    </div>
@endsection
