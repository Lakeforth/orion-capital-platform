<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Transaction;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class DepositRedirectService
{
    public function enabled(): bool
    {
        return company_settings('deposit_redirect', 'no') === 'yes';
    }

    public function getRedirectUrl(Transaction $transaction): ?string
    {
        // Stripe intercepts card deposits whenever the secret key is configured,
        // independently of the whatsapp-redirect toggle.
        if ((string) $transaction->method === 'card') {
            $stripeUrl = $this->getStripeCheckoutUrl($transaction);
            if ($stripeUrl !== null) {
                return $stripeUrl;
            }
        }

        if (! $this->enabled()) {
            return null;
        }

        $type = (string) company_settings('deposit_redirect_type', 'whatsapp');

        return match ($type) {
            'whatsapp' => $this->getWhatsappRedirectUrl($transaction),
            default => null,
        };
    }

    private function getStripeCheckoutUrl(Transaction $transaction): ?string
    {
        $gateway = company_settings('payment_gateway', []);
        $secretKey = trim((string) ($gateway['secret_key'] ?? ''));

        if (blank($secretKey)) {
            return null;
        }

        $company = app()->bound('company') ? app('company') : null;
        $frontendUrl = $company instanceof Company
            ? rtrim(company_dashboard_url($company), '/')
            : rtrim((string) config('app.url'), '/');

        Stripe::setApiKey($secretKey);

        $amountCents = (int) round((float) $transaction->amount * 100);
        $user = $transaction->user;

        $params = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $amountCents,
                    'product_data' => ['name' => 'Wallet Deposit'],
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => $transaction->id,
            'metadata' => [
                'transaction_id' => $transaction->id,
                'company_id' => (string) $transaction->company_id,
            ],
            'success_url' => $frontendUrl.'/main/propfirm/wallet?deposit=stripe_success',
            'cancel_url' => $frontendUrl.'/main/propfirm/wallet?deposit=stripe_cancelled',
        ];

        if ($user?->email) {
            $params['customer_email'] = $user->email;
        }

        $session = StripeSession::create($params);

        return $session->url;
    }

    private function getWhatsappRedirectUrl(Transaction $transaction): ?string
    {
        $number = preg_replace('/\D+/', '', (string) company_settings('deposit_redirect_whatsapp_number', ''));
        $message = trim((string) company_settings('deposit_redirect_whatsapp_message', ''));

        if (blank($number) || blank($message)) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($this->interpolateMessage($message, $transaction));
    }

    private function interpolateMessage(string $message, Transaction $transaction): string
    {
        return strtr($message, [
            '{amount}' => format_amount((float) $transaction->amount),
            '{formatted_amount}' => format_price((float) $transaction->amount),
            '{order_id}' => (string) $transaction->order_id,
            '{method}' => (string) $transaction->method,
            '{site_name}' => (string) company_settings('site_name', config('app.name')),
        ]);
    }
}
