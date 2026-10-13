<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChallengeStartedMail extends Mailable implements ShouldQueue
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
            subject: "Your {$this->data['planTitle']} challenge is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.challenge-started',
            with: [
                'siteName' => $this->companySiteName(config('app.name')),
                'logoUrl' => $this->companyLogoUrl(),
                'customerName' => $this->data['customerName'] ?? null,
                'data' => $this->data,
                'startUrl' => $this->data['startUrl'] ?? config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
