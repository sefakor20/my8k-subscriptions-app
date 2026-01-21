<?php

declare(strict_types=1);

use App\Services\AlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::flush();
});

test('alert service is disabled by default', function () {
    Config::set('services.alerts.enabled', false);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Slack alerts disabled');
        });

    $service = app(AlertService::class);
    $service->critical('Test Alert', 'This is a test');
});

test('alert service logs when webhook url not configured', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', null);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Slack alerts disabled');
        });

    $service = app(AlertService::class);
    $service->warning('Test Alert', 'This is a test');
});

test('alert service sends critical alert when enabled', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);
    $service->critical('Critical Alert', 'Something went wrong');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'hooks.slack.com');
    });
});

test('alert service sends warning alert when enabled', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);
    $service->warning('Warning Alert', 'Please check this');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'hooks.slack.com');
    });
});

test('alert service sends info alert when enabled', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);
    $service->info('Info Alert', 'Just letting you know');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'hooks.slack.com');
    });
});

test('alert service throttles duplicate alerts', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);

    $service->critical('Same Alert', 'First occurrence');
    $service->critical('Same Alert', 'Second occurrence');
    $service->critical('Same Alert', 'Third occurrence');

    Http::assertSentCount(1);
});

test('alert service allows different alerts', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);

    $service->critical('First Alert', 'First message');
    $service->critical('Second Alert', 'Second message');

    Http::assertSentCount(2);
});

test('alert service includes context in payload', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);
    $service->critical('Alert With Context', 'Test message', [
        'user_id' => '123',
        'subscription_id' => '456',
    ]);

    Http::assertSent(function ($request) {
        $body = json_encode($request->data());

        return str_contains($body, 'user_id') && str_contains($body, '123');
    });
});

test('alert service logs error when slack api fails', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');

    Http::fake([
        'hooks.slack.com/*' => Http::response('error', 500),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Failed to send Slack alert');
        });

    $service = app(AlertService::class);
    $service->critical('Test Alert', 'This should fail');
});

test('alert service uses configured channel', function () {
    Config::set('services.alerts.enabled', true);
    Config::set('services.alerts.slack_webhook_url', 'https://hooks.slack.com/test');
    Config::set('services.alerts.slack_channel', '#custom-alerts');

    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $service = app(AlertService::class);
    $service->info('Channel Test', 'Testing channel config');

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['channel'] === '#custom-alerts';
    });
});
