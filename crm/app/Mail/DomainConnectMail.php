<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainConnectMail extends Mailable implements ShouldQueue
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
            subject: 'Your ' . $this->companySiteName(config('app.name')) . ' domain has been connected',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.domain-connect',
            with: [
                'siteName'     => $this->companySiteName(config('app.name')),
                'customerName' => $this->data['customerName'] ?? $this->data['name'] ?? null,
                'data' => $this->data,
                'logoUrl' => $this->data['logoUrl'] ?? null,
                'supportUrl' => $this->data['supportUrl'] ?? null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
