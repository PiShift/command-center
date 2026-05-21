<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePublishedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from PiShift",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoices.published',
        );
    }

    public function attachments(): array
    {
        $pdf = app(InvoiceService::class)->generatePdf($this->invoice);
        $this->attachData(
            $pdf->output(),
            $this->invoice->invoice_number . '.pdf',
            ['mime' => 'application/pdf']
        );
        return [];
    }
}
