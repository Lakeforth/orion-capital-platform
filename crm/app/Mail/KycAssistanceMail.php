<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycAssistanceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailer;

    public function __construct(
        protected array $data,
        ?int $company_id = null,
    ) {
        $this->useCompanyMailer($company_id);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyFromAddress(),
            subject: $this->companySiteName(config('app.name')) . ' — Your Account is Under Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.kyc-assistance',
            with: [
                'siteName'     => $this->companySiteName(config('app.name')),
                'logoUrl'      => $this->companyLogoUrl(),
                'customerName' => $this->data['customerName'] ?? $this->data['name'] ?? null,
                'clientEmail'  => $this->data['clientEmail'] ?? $this->data['email'] ?? null,
                'supportUrl'   => $this->data['supportUrl'] ?? config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
