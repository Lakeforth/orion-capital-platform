<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccountDeposit extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailer;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected array $data,
        ?int $company_id = null,
    ) {
        $this->useCompanyMailer($company_id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $amount = $this->data['amount'];

        return new Envelope(
            from: $this->companyFromAddress(),
            subject: $this->companySiteName(config('app.name'))." account alert [Credit:$amount]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.account.deposit',
            with: [
                'amount'        => $this->data['amount'],
                'wallet_balance'=> $this->data['wallet_balance'],
                'new_balance'     => $this->data['new_balance'] ?? null,
                'txHash'          => $this->data['txHash'] ?? null,
                'network'         => $this->data['network'] ?? null,
                'senderAddress'   => $this->data['senderAddress'] ?? null,
                'transactionDate' => $this->data['transactionDate'] ?? null,
                'narration'       => $this->data['narration'] ?? null,
                'wallet'        => $this->data['wallet'],
                'name'          => $this->data['name'],
                'customerName'  => $this->data['customerName'] ?? $this->data['name'] ?? null,
                'siteName'      => $this->companySiteName(config('app.name')),
                'logoUrl'      => $this->companyLogoUrl(),
                'ctaUrl'        => $this->data['ctaUrl'] ?? config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
