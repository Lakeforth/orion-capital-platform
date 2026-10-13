<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalApprovedMail extends Mailable implements ShouldQueue
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
        $amount = $this->data['amount'] ?? '';

        return new Envelope(
            from: $this->companyFromAddress(),
            subject: $this->companySiteName(config('app.name')) . " — Withdrawal Approved [{$amount}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.withdrawal-approved',
            with: [
                'siteName'        => $this->companySiteName(config('app.name')),
                'logoUrl'      => $this->companyLogoUrl(),
                'customerName'    => $this->data['customerName'] ?? $this->data['name'] ?? null,
                'amount'          => $this->data['amount'] ?? null,
                'initialBalance'  => $this->data['initialBalance'] ?? null,
                'newBalance'      => $this->data['newBalance'] ?? null,
                'txHash'          => $this->data['txHash'] ?? null,
                'network'         => $this->data['network'] ?? null,
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
