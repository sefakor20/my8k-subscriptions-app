<?php

declare(strict_types=1);

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\Admin\SupportTicketsList;
use App\Livewire\Admin\TicketDetailModal;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

test('admin can access support tickets list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.support.tickets'))
        ->assertOk()
        ->assertSeeLivewire(SupportTicketsList::class);
});

test('non-admin cannot access support tickets list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.support.tickets'))
        ->assertForbidden();
});

test('guest is redirected to login from support tickets list', function () {
    $response = $this->get(route('admin.support.tickets'));

    $response->assertRedirect(route('login'));
});

test('support tickets list displays tickets', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $ticket = SupportTicket::factory()
        ->for($customer)
        ->create(['subject' => 'Test Ticket Subject']);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->assertSee('Test Ticket Subject')
        ->assertSee($customer->name);
});

test('support tickets list can filter by status', function () {
    $admin = User::factory()->admin()->create();

    $openTicket = SupportTicket::factory()->open()->create(['subject' => 'My Open Ticket']);
    $closedTicket = SupportTicket::factory()->closed()->create(['subject' => 'My Closed Ticket']);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->assertSee('My Open Ticket')
        ->assertSee('My Closed Ticket')
        ->set('filterStatus', 'open')
        ->assertSee('My Open Ticket')
        ->assertDontSee('My Closed Ticket')
        ->set('filterStatus', 'closed')
        ->assertSee('My Closed Ticket')
        ->assertDontSee('My Open Ticket');
});

test('support tickets list can filter by category', function () {
    $admin = User::factory()->admin()->create();

    $technicalTicket = SupportTicket::factory()->create([
        'subject' => 'Technical Issue',
        'category' => TicketCategory::Technical,
    ]);
    $billingTicket = SupportTicket::factory()->create([
        'subject' => 'Billing Question',
        'category' => TicketCategory::Billing,
    ]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterCategory', 'technical')
        ->assertSee('Technical Issue')
        ->assertDontSee('Billing Question');
});

test('support tickets list can filter by priority', function () {
    $admin = User::factory()->admin()->create();

    $urgentTicket = SupportTicket::factory()->urgent()->create(['subject' => 'Urgent Issue']);
    $normalTicket = SupportTicket::factory()->create([
        'subject' => 'Normal Issue',
        'priority' => TicketPriority::Normal,
    ]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterPriority', 'urgent')
        ->assertSee('Urgent Issue')
        ->assertDontSee('Normal Issue');
});

test('support tickets list can filter by assignment', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $myTicket = SupportTicket::factory()->create([
        'subject' => 'My Assigned Ticket',
        'assigned_to' => $admin->id,
    ]);
    $otherTicket = SupportTicket::factory()->create([
        'subject' => 'Other Ticket',
        'assigned_to' => $otherAdmin->id,
    ]);
    $unassignedTicket = SupportTicket::factory()->create([
        'subject' => 'Unassigned Ticket',
        'assigned_to' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterAssignment', 'mine')
        ->assertSee('My Assigned Ticket')
        ->assertDontSee('Other Ticket')
        ->assertDontSee('Unassigned Ticket');
});

test('support tickets list can search by subject', function () {
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->create(['subject' => 'Password reset help']);
    SupportTicket::factory()->create(['subject' => 'Billing question']);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('search', 'password')
        ->assertSee('Password reset help')
        ->assertDontSee('Billing question');
});

test('support tickets list can search by customer email', function () {
    $admin = User::factory()->admin()->create();
    $customer1 = User::factory()->create(['email' => 'john@example.com']);
    $customer2 = User::factory()->create(['email' => 'jane@example.com']);

    SupportTicket::factory()->for($customer1)->create(['subject' => 'John Ticket']);
    SupportTicket::factory()->for($customer2)->create(['subject' => 'Jane Ticket']);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('search', 'john@example')
        ->assertSee('John Ticket')
        ->assertDontSee('Jane Ticket');
});

test('admin can view ticket details', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $ticket = SupportTicket::factory()
        ->for($customer)
        ->create(['subject' => 'Detailed Ticket']);

    SupportMessage::factory()
        ->for($ticket, 'ticket')
        ->for($customer)
        ->create(['message' => 'Initial customer message']);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertSee('Detailed Ticket')
        ->assertSee($customer->name)
        ->assertSee('Initial customer message');
});

test('admin can reply to ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is an admin reply to the customer ticket.')
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'message' => 'This is an admin reply to the customer ticket.',
        'is_internal_note' => false,
    ]);
});

test('admin can add internal note', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is an internal note not visible to customer.')
        ->set('isInternalNote', true)
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'message' => 'This is an internal note not visible to customer.',
        'is_internal_note' => true,
    ]);
});

test('admin can change ticket status', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::InProgress->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::InProgress);
});

test('admin can resolve ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::Resolved->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Resolved);
    expect($ticket->resolved_at)->not->toBeNull();
});

test('admin can close ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::Closed->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed);
    expect($ticket->closed_at)->not->toBeNull();
});

test('admin can assign ticket to themselves', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => null]);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', $admin->id)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($admin->id);
});

test('admin can assign ticket to another admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => null]);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', $otherAdmin->id)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($otherAdmin->id);
});

test('admin can unassign ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', null)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->assigned_to)->toBeNull();
});

test('reply message validation requires minimum length', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'short')
        ->call('sendReply')
        ->assertHasErrors(['replyMessage']);
});

test('first admin response sets first_response_at timestamp', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $ticket = SupportTicket::factory()
        ->for($customer)
        ->create(['first_response_at' => null]);

    expect($ticket->first_response_at)->toBeNull();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is the first admin response to the ticket.')
        ->call('sendReply');

    $ticket->refresh();
    expect($ticket->first_response_at)->not->toBeNull();
});

test('support tickets list paginates results', function () {
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->count(25)->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->assertSet('filterStatus', 'all')
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->perPage() === 20 && $tickets->total() === 25;
        });
});

test('non-admin cannot view ticket details', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create();

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertForbidden();
});
