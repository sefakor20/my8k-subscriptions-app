<?php

declare(strict_types=1);

use App\Livewire\Admin\ResellerCreditsManagement;
use App\Livewire\Admin\ResellerCreditsWidget;
use App\Models\ResellerCreditLog;
use App\Models\User;
use App\Services\Admin\ResellerCreditsService;
use Livewire\Livewire;

test('admin can access credits management page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSuccessful();
    $response->assertSeeLivewire(ResellerCreditsManagement::class);
});

test('non-admin cannot access credits management page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/credits');

    $response->assertForbidden();
});

test('guest is redirected to login from credits page', function () {
    $response = $this->get('/admin/credits');

    $response->assertRedirect(route('login'));
});

test('credits management page displays header', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->assertSee('Reseller Credits Management')
        ->assertSee('Monitor your My8K reseller credit balance');
});

test('credits widget displays metrics', function () {
    $admin = User::factory()->admin()->create();

    // Create some test data
    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 1000,
        'created_at' => now()->subDay(),
    ]);

    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 950,
        'previous_balance' => 1000,
        'change_amount' => 50,
        'change_type' => 'debit',
    ]);

    Livewire::actingAs($admin)
        ->test(ResellerCreditsWidget::class)
        ->assertSee('Reseller Credits')
        ->assertSee('Current Balance')
        ->assertSee('24h Change')
        ->assertSee('7d Change')
        ->assertSee('Avg Daily Usage')
        ->assertSee('Depletion Est.');
});

test('can refresh balance from widget', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsWidget::class)
        ->call('refreshBalance')
        ->assertDispatched('balance-refreshed');
});

test('can refresh balance from management page', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->call('refreshBalance')
        ->assertDispatched('balance-refreshed');
});

test('date range filter works', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->assertSet('dateRange', 30)
        ->set('dateRange', 7)
        ->assertSet('dateRange', 7)
        ->set('dateRange', 90)
        ->assertSet('dateRange', 90);
});

test('transaction type filter works', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->assertSet('filterType', 'all')
        ->set('filterType', 'debit')
        ->assertSet('filterType', 'debit')
        ->set('filterType', 'snapshot')
        ->assertSet('filterType', 'snapshot');
});

test('displays transaction history table', function () {
    $admin = User::factory()->admin()->create();

    // Create transaction logs
    ResellerCreditLog::factory()->debit()->create([
        'balance' => 950,
        'previous_balance' => 1000,
        'change_amount' => 50,
        'reason' => 'New account provisioning',
    ]);

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSee('Transaction History');
    $response->assertSee('New account provisioning');
    $response->assertSee('950');
    $response->assertSee('1,000');
});

test('displays empty state when no transactions', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSee('No Transaction History');
    $response->assertSee('No credit log entries found');
});

test('displays metrics cards', function () {
    $admin = User::factory()->admin()->create();

    ResellerCreditLog::factory()->snapshot()->create(['balance' => 500]);

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSee('Current Balance');
    $response->assertSee('24h Change');
    $response->assertSee('Avg Daily Usage');
    $response->assertSee('Depletion Estimate');
});

test('displays alert thresholds info', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSee('Alert Thresholds');
    $response->assertSee('500');
    $response->assertSee('200');
    $response->assertSee('50');
});

test('displays charts', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/credits');

    $response->assertSee('Credit Balance History');
    $response->assertSee('Daily Credit Usage');
});

test('alert level is determined correctly', function () {
    $creditsService = app(ResellerCreditsService::class);

    expect($creditsService->determineAlertLevel(1000))->toBe('ok');
    expect($creditsService->determineAlertLevel(500))->toBe('warning');
    expect($creditsService->determineAlertLevel(200))->toBe('critical');
    expect($creditsService->determineAlertLevel(50))->toBe('urgent');
    expect($creditsService->determineAlertLevel(10))->toBe('urgent');
});

test('should trigger alert when balance is low', function () {
    $creditsService = app(ResellerCreditsService::class);

    expect($creditsService->shouldTriggerAlert(1000))->toBeFalse();
    expect($creditsService->shouldTriggerAlert(500))->toBeTrue();
    expect($creditsService->shouldTriggerAlert(200))->toBeTrue();
    expect($creditsService->shouldTriggerAlert(50))->toBeTrue();
});

test('balance history is computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create balance history
    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 1000,
        'created_at' => now()->subDays(5),
    ]);

    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 950,
        'created_at' => now()->subDays(3),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class);

    $balanceHistory = $component->instance()->balanceHistory;

    expect($balanceHistory)->toHaveKeys(['labels', 'data', 'error']);
    expect($balanceHistory['labels'])->toBeArray();
    expect($balanceHistory['data'])->toBeArray();
});

test('daily usage is computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create debit transactions
    ResellerCreditLog::factory()->debit()->create([
        'balance' => 950,
        'previous_balance' => 1000,
        'change_amount' => 50,
        'created_at' => now()->subDays(2),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class);

    $dailyUsage = $component->instance()->dailyUsage;

    expect($dailyUsage)->toHaveKeys(['labels', 'data', 'error']);
    expect($dailyUsage['labels'])->toBeArray();
    expect($dailyUsage['data'])->toBeArray();
});

test('metrics are computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create test data
    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 1000,
        'created_at' => now()->subDay(),
    ]);

    ResellerCreditLog::factory()->debit()->create([
        'balance' => 950,
        'previous_balance' => 1000,
        'change_amount' => 50,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class);

    $metrics = $component->instance()->metrics;

    expect($metrics)->toHaveKeys([
        'currentBalance',
        'change24h',
        'change7d',
        'avgDailyUsage',
        'estimatedDepletionDays',
        'alertLevel',
        'error',
    ]);
});

test('changing date range updates computed properties', function () {
    $admin = User::factory()->admin()->create();

    // Create data
    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 1000,
        'created_at' => now()->subDays(40),
    ]);

    ResellerCreditLog::factory()->snapshot()->create([
        'balance' => 950,
        'created_at' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->assertSet('dateRange', 30);

    // Change to 60 days
    $component->set('dateRange', 60);

    // Should reload data
    expect($component->instance()->balanceHistory)->toBeArray();
    expect($component->instance()->dailyUsage)->toBeArray();
});

test('filter type filters transaction list', function () {
    $admin = User::factory()->admin()->create();

    // Create different transaction types
    ResellerCreditLog::factory()->debit()->create([
        'balance' => 950,
        'previous_balance' => 1000,
        'change_amount' => 50,
    ]);

    ResellerCreditLog::factory()->credit()->create([
        'balance' => 1050,
        'previous_balance' => 950,
        'change_amount' => 100,
    ]);

    ResellerCreditLog::factory()->snapshot()->create(['balance' => 1050]);

    // Test all types
    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->set('filterType', 'all')
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 3;
        });

    // Test debits only
    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->set('filterType', 'debit')
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1;
        });

    // Test credits only
    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->set('filterType', 'credit')
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1;
        });

    // Test snapshots only
    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->set('filterType', 'snapshot')
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1;
        });
});

test('transaction list is paginated', function () {
    $admin = User::factory()->admin()->create();

    // Create 25 transactions (more than 20 per page)
    ResellerCreditLog::factory()->count(25)->create();

    Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class)
        ->assertViewHas('logs', function ($logs) {
            return $logs->perPage() === 20
                && $logs->total() === 25
                && $logs->lastPage() === 2;
        });
});

test('widget can be rendered', function () {
    $admin = User::factory()->admin()->create();

    // Create some balance data
    ResellerCreditLog::factory()->snapshot()->create(['balance' => 1000]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\ResellerCreditsWidget::class)
        ->assertSee('Reseller Credits')
        ->assertSee('Current Balance');
});

test('widget link navigates to credits page', function () {
    $admin = User::factory()->admin()->create();

    ResellerCreditLog::factory()->snapshot()->create(['balance' => 1000]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSee('View Detailed Report');
    $response->assertSee(route('admin.credits', absolute: false));
});

test('charts show proper data structure', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(ResellerCreditsManagement::class);

    $balanceHistory = $component->instance()->balanceHistory;
    $dailyUsage = $component->instance()->dailyUsage;

    // Should have proper structure
    expect($balanceHistory)->toHaveKeys(['labels', 'data', 'error']);
    expect($dailyUsage)->toHaveKeys(['labels', 'data', 'error']);
});

test('credits link is in sidebar navigation', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSee('Credits');
    $response->assertSee(route('admin.credits', absolute: false));
});

test('alert thresholds are returned correctly', function () {
    $creditsService = app(ResellerCreditsService::class);

    $thresholds = $creditsService->getAlertThresholds();

    expect($thresholds)->toHaveKeys(['warning', 'critical', 'urgent']);
    expect($thresholds['warning'])->toBe(500);
    expect($thresholds['critical'])->toBe(200);
    expect($thresholds['urgent'])->toBe(50);
});
