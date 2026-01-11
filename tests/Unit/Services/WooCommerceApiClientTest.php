<?php

declare(strict_types=1);

use App\Services\WooCommerceApiClient;
use Automattic\WooCommerce\Client;

beforeEach(function () {
    config([
        'services.woocommerce.store_url' => 'https://test-store.com',
        'services.woocommerce.consumer_key' => 'ck_test_key',
        'services.woocommerce.consumer_secret' => 'cs_test_secret',
        'services.woocommerce.version' => 'wc/v3',
    ]);
});

test('gets a single order successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('get')
        ->once()
        ->with('orders/12345')
        ->andReturn([
            'id' => 12345,
            'status' => 'completed',
            'total' => '99.99',
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getOrder('12345');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['id'])->toBe(12345)
        ->and($result['data']['status'])->toBe('completed');
});

test('gets multiple orders successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('get')
        ->once()
        ->with('orders', ['status' => 'completed', 'per_page' => 10])
        ->andReturn([
            ['id' => 1, 'status' => 'completed'],
            ['id' => 2, 'status' => 'completed'],
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getOrders(['status' => 'completed', 'per_page' => 10]);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2)
        ->and($result['data'])->toHaveCount(2);
});

test('gets a single subscription successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('get')
        ->once()
        ->with('subscriptions/456')
        ->andReturn([
            'id' => 456,
            'status' => 'active',
            'billing_period' => 'month',
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getSubscription('456');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['id'])->toBe(456)
        ->and($result['data']['status'])->toBe('active');
});

test('adds order note successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('post')
        ->once()
        ->with('orders/12345/notes', [
            'note' => 'IPTV account provisioned successfully',
            'customer_note' => false,
        ])
        ->andReturn([
            'id' => 789,
            'note' => 'IPTV account provisioned successfully',
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->addOrderNote('12345', 'IPTV account provisioned successfully');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['note'])->toBe('IPTV account provisioned successfully');
});

test('adds subscription note successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('post')
        ->once()
        ->with('subscriptions/456/notes', [
            'note' => 'Subscription renewed',
            'customer_note' => true,
        ])
        ->andReturn([
            'id' => 890,
            'note' => 'Subscription renewed',
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->addSubscriptionNote('456', 'Subscription renewed', true);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['note'])->toBe('Subscription renewed');
});

test('updates order successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('put')
        ->once()
        ->with('orders/12345', ['status' => 'processing'])
        ->andReturn([
            'id' => 12345,
            'status' => 'processing',
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->updateOrder('12345', ['status' => 'processing']);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['status'])->toBe('processing');
});

test('handles general exception', function () {
    $mockClient = $this->mock(Client::class);
    $exception = new Exception('Connection failed');

    $mockClient->shouldReceive('get')
        ->once()
        ->with('orders/99999')
        ->andThrow($exception);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getOrder('99999');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Connection failed')
        ->and($result['error_code'])->toBeString();
});

test('handles timeout exception', function () {
    $mockClient = $this->mock(Client::class);
    $exception = new Exception('Request timeout after 30 seconds');

    $mockClient->shouldReceive('get')
        ->once()
        ->with('orders/12345')
        ->andThrow($exception);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getOrder('12345');

    expect($result['success'])->toBeFalse()
        ->and($result['error_code'])->toBe('ERR_TIMEOUT');
});

test('handles connection exception', function () {
    $mockClient = $this->mock(Client::class);
    $exception = new Exception('Failed to connect to server');

    $mockClient->shouldReceive('get')
        ->once()
        ->with('orders/12345')
        ->andThrow($exception);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->getOrder('12345');

    expect($result['success'])->toBeFalse()
        ->and($result['error_code'])->toBe('ERR_CONNECTION');
});

test('tests connection successfully', function () {
    $mockClient = $this->mock(Client::class);
    $mockClient->shouldReceive('get')
        ->once()
        ->with('system_status')
        ->andReturn([
            'environment' => [
                'home_url' => 'https://test-store.com',
                'wp_version' => '6.0',
                'wc_version' => '7.0',
            ],
        ]);

    $client = new WooCommerceApiClient();
    $reflection = new ReflectionClass($client);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($client, $mockClient);

    $result = $client->testConnection();

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Connection successful')
        ->and($result['data']['environment'])->toBeArray();
});
