<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUnreadMessageMail extends Mailable implements ShouldQueue
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
        $client = $this->data['clientName'] ?? 'A client';

        return new Envelope(
            from: $this->companyFromAddress(),
            subject: "[{$this->companySiteName(config('app.name'))}] New Message from {$client}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin.unread-message',
            with: [
                'siteName'       => $this->companySiteName(config('app.name')),
                'adminName'      => $this->data['adminName'] ?? 'Admin',
                'clientName'     => $this->data['clientName'] ?? null,
                'messageTime'    => $this->data['messageTime'] ?? null,
                'messagePreview' => $this->data['messagePreview'] ?? null,
                'ctaUrl'         => $this->data['ctaUrl'] ?? config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
