<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminWithdrawalPendingMail extends Mailable implements ShouldQueue
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
        $amount = $this->data['amount'] ?? '';

        return new Envelope(
            from: $this->companyFromAddress(),
            subject: "[{$this->companySiteName(config('app.name'))}] New Withdrawal Request — {$client} ({$amount})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin.withdrawal-pending',
            with: [
                'siteName'        => $this->companySiteName(config('app.name')),
                'adminName'       => $this->data['adminName'] ?? 'Admin',
                'clientName'      => $this->data['clientName'] ?? null,
                'amount'          => $this->data['amount'] ?? null,
                'method'          => $this->data['method'] ?? null,
                'transactionDate' => $this->data['transactionDate'] ?? null,
                'ctaUrl'          => $this->data['ctaUrl'] ?? config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
