<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    public function token(): string
    {
        return (string) config('services.telegram-bot-api.token');
    }

    /** Bot's own @username, for building https://t.me/<username>?start=<token> deep links. */
    public function username(): string
    {
        return (string) config('services.telegram-bot-api.username', 'livetrader_engine_bot');
    }

    public function webhookSecret(): ?string
    {
        $secret = trim((string) config('services.telegram-bot-api.webhook_secret'));

        return $secret !== '' ? $secret : null;
    }

    public function webhookUrl(): string
    {
        $configured = trim((string) config('services.telegram-bot-api.webhook_url'));

        if ($configured !== '') {
            return $configured;
        }

        return route('webhooks.telegram');
    }

    public function send(string $method, array $payload = []): Response
    {
        return Http::asForm()->post($this->endpoint($method), $payload);
    }

    public function setWebhook(): Response
    {
        $payload = [
            'url' => $this->webhookUrl(),
        ];

        if ($this->webhookSecret()) {
            $payload['secret_token'] = $this->webhookSecret();
        }

        return $this->send('setWebhook', $payload);
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): Response
    {
        return $this->send('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates ? 'true' : 'false',
        ]);
    }

    public function getWebhookInfo(): Response
    {
        return $this->send('getWebhookInfo');
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): Response
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert ? 'true' : 'false',
        ];

        if ($text !== null && trim($text) !== '') {
            $payload['text'] = $text;
        }

        return $this->send('answerCallbackQuery', $payload);
    }

    public function sendMessage(string|int $chatId, string $text): Response
    {
        return $this->send('sendMessage', [
            'chat_id' => (string) $chatId,
            'text' => $text,
        ]);
    }

    public function editMessageReplyMarkup(string|int $chatId, string|int $messageId, array $markup = []): Response
    {
        return $this->send('editMessageReplyMarkup', [
            'chat_id' => (string) $chatId,
            'message_id' => (string) $messageId,
            'reply_markup' => json_encode($markup, JSON_UNESCAPED_SLASHES),
        ]);
    }

    protected function endpoint(string $method): string
    {
        return sprintf(
            'https://api.telegram.org/bot%s/%s',
            $this->token(),
            $method
        );
    }
}
