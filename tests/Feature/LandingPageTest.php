<?php

declare(strict_types=1);

use App\Livewire\Landing\PricingSection;
use App\Models\Plan;
use Livewire\Livewire;

it('displays the landing page successfully', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('MoTv');
});

it('displays the hero section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Unlimited Entertainment');
});

it('displays the features section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Why Choose');
});

it('displays the pricing section livewire component', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire(PricingSection::class);
});

it('displays the about section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('About Us');
});

it('displays the benefits section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('The MoTv')
        ->assertSee('Advantage');
});

it('displays the cta section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Ready to Transform');
});

it('displays the content showcase section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Enjoy Your Time')
        ->assertSee('Enjoy Watching Now');
});

it('displays active monthly plans in pricing section', function () {
    $plan = Plan::factory()->monthly()->active()->create([
        'name' => 'Test Monthly Plan',
    ]);

    Livewire::test(PricingSection::class)
        ->assertSee('Test Monthly Plan')
        ->assertSee($plan->formattedPrice());
});

it('filters plans by billing interval', function () {
    $monthlyPlan = Plan::factory()->monthly()->active()->create([
        'name' => 'Monthly Test Plan',
    ]);
    $yearlyPlan = Plan::factory()->yearly()->active()->create([
        'name' => 'Yearly Test Plan',
    ]);

    Livewire::test(PricingSection::class)
        ->assertSee('Monthly Test Plan')
        ->assertDontSee('Yearly Test Plan')
        ->call('setInterval', 'yearly')
        ->assertSee('Yearly Test Plan')
        ->assertDontSee('Monthly Test Plan');
});

it('does not display inactive plans', function () {
    $activePlan = Plan::factory()->monthly()->active()->create([
        'name' => 'Active Test Plan',
    ]);
    $inactivePlan = Plan::factory()->monthly()->inactive()->create([
        'name' => 'Inactive Test Plan',
    ]);

    Livewire::test(PricingSection::class)
        ->assertSee('Active Test Plan')
        ->assertDontSee('Inactive Test Plan');
});

it('displays all billing interval tabs', function () {
    Livewire::test(PricingSection::class)
        ->assertSee('Monthly')
        ->assertSee('Quarterly')
        ->assertSee('Yearly');
});

it('defaults to monthly interval', function () {
    Livewire::test(PricingSection::class)
        ->assertSet('selectedInterval', 'monthly');
});

it('shows empty state when no plans available', function () {
    // Don't create any plans
    Livewire::test(PricingSection::class)
        ->assertSee('No plans available');
});

it('shows register link for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Register Now');
});

it('shows dashboard link for authenticated users', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Dashboard');
});
