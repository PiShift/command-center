<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice        $invoice,
        public readonly InvoicePayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment received — Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoices.payment-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
