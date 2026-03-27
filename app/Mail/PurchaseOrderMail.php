<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = Setting::get('po_reply_to_email', Setting::get('company_email'));

        return new Envelope(
            subject: "Orden de Compra {$this->purchaseOrder->po_number}",
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order',
            with: [
                'purchaseOrder' => $this->purchaseOrder,
                'companyName' => Setting::get('company_name'),
            ],
        );
    }

    public function attachments(): array
    {
        $pdfContent = app(PurchaseOrderPdfService::class)->generateContent($this->purchaseOrder);

        return [
            Attachment::fromData(fn () => $pdfContent, "OC-{$this->purchaseOrder->po_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
