<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use RuntimeException;

class AlertService
{
    private const LEVEL_CRITICAL = 'critical';

    private const LEVEL_WARNING = 'warning';

    private const LEVEL_INFO = 'info';

    private const COLORS = [
        self::LEVEL_CRITICAL => '#dc2626',
        self::LEVEL_WARNING => '#f59e0b',
        self::LEVEL_INFO => '#3b82f6',
    ];

    private const EMOJIS = [
        self::LEVEL_CRITICAL => ':rotating_light:',
        self::LEVEL_WARNING => ':warning:',
        self::LEVEL_INFO => ':information_source:',
    ];

    public function critical(string $title, string $message, array $context = []): void
    {
        $this->send(self::LEVEL_CRITICAL, $title, $message, $context);
    }

    public function warning(string $title, string $message, array $context = []): void
    {
        $this->send(self::LEVEL_WARNING, $title, $message, $context);
    }

    public function info(string $title, string $message, array $context = []): void
    {
        $this->send(self::LEVEL_INFO, $title, $message, $context);
    }

    public function send(string $level, string $title, string $message, array $context = []): void
    {
        if (! $this->isEnabled()) {
            Log::info('Slack alerts disabled, logging instead', [
                'level' => $level,
                'title' => $title,
                'message' => $message,
                'context' => $context,
            ]);

            return;
        }

        $throttleKey = $this->getThrottleKey($level, $title);
        $throttleMinutes = $this->getThrottleMinutes($level);

        if (! $this->shouldSendAlert($throttleKey, $throttleMinutes)) {
            Log::debug('Alert throttled', ['key' => $throttleKey]);

            return;
        }

        try {
            $this->sendToSlack($level, $title, $message, $context);
            $this->markAlertSent($throttleKey, $throttleMinutes);
        } catch (Throwable $e) {
            Log::error('Failed to send Slack alert', [
                'level' => $level,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendToSlack(string $level, string $title, string $message, array $context): void
    {
        $webhookUrl = config('services.alerts.slack_webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('Slack webhook URL not configured');

            return;
        }

        $payload = $this->buildSlackPayload($level, $title, $message, $context);

        $response = Http::post($webhookUrl, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Slack API returned error: ' . $response->body());
        }
    }

    private function buildSlackPayload(string $level, string $title, string $message, array $context): array
    {
        $emoji = self::EMOJIS[$level] ?? ':bell:';
        $color = self::COLORS[$level] ?? '#6b7280';
        $environment = app()->environment();
        $appName = config('app.name');

        $fields = [];

        if (! empty($context)) {
            foreach ($context as $key => $value) {
                $fields[] = [
                    'type' => 'mrkdwn',
                    'text' => "*{$key}:*\n" . (is_array($value) ? json_encode($value) : $value),
                ];
            }
        }

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "{$emoji} {$title}",
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Environment:* {$environment} | *App:* {$appName} | *Time:* " . now()->toDateTimeString(),
                    ],
                ],
            ],
        ];

        if (! empty($fields)) {
            $blocks[] = [
                'type' => 'section',
                'fields' => array_slice($fields, 0, 10),
            ];
        }

        $blocks[] = [
            'type' => 'divider',
        ];

        return [
            'channel' => config('services.alerts.slack_channel', '#alerts'),
            'username' => config('services.alerts.slack_username', 'System Alerts'),
            'icon_emoji' => $emoji,
            'attachments' => [
                [
                    'color' => $color,
                    'blocks' => $blocks,
                ],
            ],
        ];
    }

    private function isEnabled(): bool
    {
        return config('services.alerts.enabled', false)
            && ! empty(config('services.alerts.slack_webhook_url'));
    }

    private function shouldSendAlert(string $key, int $throttleMinutes): bool
    {
        return ! Cache::has($key);
    }

    private function markAlertSent(string $key, int $throttleMinutes): void
    {
        Cache::put($key, now(), now()->addMinutes($throttleMinutes));
    }

    private function getThrottleKey(string $level, string $title): string
    {
        return 'alert_throttle:' . md5($level . ':' . $title);
    }

    private function getThrottleMinutes(string $level): int
    {
        return match ($level) {
            self::LEVEL_CRITICAL => 15,
            self::LEVEL_WARNING => 30,
            self::LEVEL_INFO => 60,
            default => 30,
        };
    }
}
