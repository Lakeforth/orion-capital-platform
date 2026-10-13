<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
        {action=set : The webhook action to run (set, info, delete)}
        {--drop-pending : Drop pending Telegram updates when deleting the webhook}';

    protected $description = 'Manage the Telegram bot webhook using the currently configured bot token';

    public function handle(TelegramBotService $telegramBotService): int
    {
        $token = trim($telegramBotService->token());

        if ($token === '' || $token === 'YOUR BOT TOKEN HERE') {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'set' => $this->setWebhook($telegramBotService),
            'info' => $this->showWebhookInfo($telegramBotService),
            'delete' => $this->deleteWebhook($telegramBotService),
            default => $this->invalidAction($action),
        };
    }

    protected function setWebhook(TelegramBotService $telegramBotService): int
    {
        $url = trim($telegramBotService->webhookUrl());

        if ($url === '') {
            $this->error('Telegram webhook URL could not be resolved.');

            return self::FAILURE;
        }

        $response = $telegramBotService->setWebhook();

        return $this->renderTelegramResponse(
            $response->json(),
            sprintf('Webhook set to %s', $url)
        );
    }

    protected function showWebhookInfo(TelegramBotService $telegramBotService): int
    {
        $response = $telegramBotService->getWebhookInfo();

        return $this->renderTelegramResponse($response->json(), 'Webhook info retrieved.');
    }

    protected function deleteWebhook(TelegramBotService $telegramBotService): int
    {
        $response = $telegramBotService->deleteWebhook((bool) $this->option('drop-pending'));

        return $this->renderTelegramResponse($response->json(), 'Webhook deleted.');
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Invalid action [{$action}]. Use set, info, or delete.");

        return self::FAILURE;
    }

    protected function renderTelegramResponse(array $payload, string $successMessage): int
    {
        if (($payload['ok'] ?? false) !== true) {
            $this->error((string) ($payload['description'] ?? 'Telegram API request failed.'));

            return self::FAILURE;
        }

        $this->info($successMessage);

        $result = $payload['result'] ?? null;

        if (is_array($result)) {
            foreach ($result as $key => $value) {
                $this->line(sprintf('%s: %s', $key, $this->stringifyValue($value)));
            }
        } elseif ($result !== null) {
            $this->line($this->stringifyValue($result));
        }

        return self::SUCCESS;
    }

    protected function stringifyValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string) $value;
    }
}
