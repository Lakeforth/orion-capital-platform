<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailer;

    public function __construct(
        public array $data,
        ?int $company_id = null
    ) {
        $this->useCompanyMailer($company_id);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyFromAddress(),
            subject: $this->data['title'] ?? 'Message from '.$this->companySiteName(config('app.name')),
        );
    }

    public function content(): Content
    {
        $template = $this->data['template'] ?? null;

        $view = match ($template) {
            'ai_trade' => 'mail.ai_trade_message',
            'recovery_welcome' => 'mail.recovery-welcome',
            'campaign_welcome' => 'mail.campaign-welcome',
            'staff_alert' => 'mail.staff-alert', // internal alerts to a lead's staff (StaffLeadAlerts)
            default => 'mail.message',
        };

        $with = [
            'title' => $this->data['title'] ?? 'Message from '.$this->companySiteName(config('app.name')),
            'content' => $this->data['message'] ?? null,
            'user' => $this->data['user'] ?? null,
            'name' => $this->data['name'] ?? null,
            'data' => $this->data,
            'siteName' => $this->companySiteName(config('app.name')),
            'logoUrl' => $this->companyLogoUrl(),
        ];

        if ($template === 'campaign_welcome') {
            $with = array_merge($with, [
                'logoUrl' => $this->data['logo_url'] ?? null,
                'siteName' => $this->data['site_name'] ?? config('app.name'),
                'heading' => $this->data['heading'] ?? '',
                'greeting' => $this->data['greeting'] ?? '',
                'bodyText' => $this->data['body_text'] ?? '',
                'usernameLabel' => $this->data['username_label'] ?? 'Username:',
                'email' => $this->data['email'] ?? '',
                'passwordLabel' => $this->data['password_label'] ?? 'Password:',
                'plainPassword' => $this->data['plain_password'] ?? '',
                'ctaText' => $this->data['cta_text'] ?? '',
                'ctaUrl' => $this->data['cta_url'] ?? '',
                'frontendUrl' => $this->data['frontend_url'] ?? '',
                'supportUrl' => $this->data['support_url'] ?? '',
            ]);
        }

        return new Content(view: $view, with: $with);
    }

    public function attachments(): array
    {
        return [];
    }
}
