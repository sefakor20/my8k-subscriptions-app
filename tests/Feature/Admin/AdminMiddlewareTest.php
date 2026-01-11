<?php

declare(strict_types=1);

use App\Models\User;

test('admin can access admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
});

test('non-admin user gets 403 on admin dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing admin dashboard', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect(route('login'));
});

test('admin routes are protected by auth middleware', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect(route('login'));
});

test('admin can see admin navigation in sidebar', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertSee('Admin');
    $response->assertSee('Subscriptions');
    $response->assertSee('Orders');
    $response->assertSee('Failed Jobs');
});

test('non-admin cannot see admin navigation in sidebar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('Admin');
});
