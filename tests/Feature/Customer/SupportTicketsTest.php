<?php

declare(strict_types=1);

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\Customer\CreateTicketModal;
use App\Livewire\Customer\MyTickets;
use App\Livewire\Customer\TicketDetailModal;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('customer can access my tickets page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/support/my-tickets');

    $response->assertSuccessful();
    $response->assertSeeLivewire(MyTickets::class);
});

test('guest is redirected to login from my tickets page', function () {
    $response = $this->get('/support/my-tickets');

    $response->assertRedirect(route('login'));
});

test('admin cannot create tickets', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CreateTicketModal::class)
        ->call('openModal')
        ->assertForbidden();
});

test('my tickets page displays user tickets', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $myTicket = SupportTicket::factory()->create(['user_id' => $user->id]);
    $otherTicket = SupportTicket::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get('/support/my-tickets');

    $response->assertSee($myTicket->subject);
    $response->assertDontSee($otherTicket->subject);
});

test('customer can filter tickets by status', function () {
    $user = User::factory()->create();

    $openTicket = SupportTicket::factory()->create(['user_id' => $user->id]);
    $closedTicket = SupportTicket::factory()->closed()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(MyTickets::class)
        ->set('filterStatus', 'open')
        ->assertSee($openTicket->subject)
        ->assertDontSee($closedTicket->subject);

    Livewire::actingAs($user)
        ->test(MyTickets::class)
        ->set('filterStatus', 'closed')
        ->assertDontSee($openTicket->subject)
        ->assertSee($closedTicket->subject);
});

test('customer can search tickets', function () {
    $user = User::factory()->create();

    $ticket1 = SupportTicket::factory()->create([
        'user_id' => $user->id,
        'subject' => 'Login Issue',
    ]);

    $ticket2 = SupportTicket::factory()->create([
        'user_id' => $user->id,
        'subject' => 'Payment Problem',
    ]);

    Livewire::actingAs($user)
        ->test(MyTickets::class)
        ->set('search', 'Login')
        ->assertSee($ticket1->subject)
        ->assertDontSee($ticket2->subject);
});

test('tickets are paginated', function () {
    $user = User::factory()->create();

    SupportTicket::factory()->count(20)->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(MyTickets::class)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->perPage() === 15 && $tickets->total() === 20;
        });
});

test('customer can open create ticket modal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicketModal::class)
        ->call('openModal')
        ->assertSet('show', true);
});

test('customer can create a ticket', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicketModal::class)
        ->call('openModal')
        ->set('subject', 'Test Issue')
        ->set('category', TicketCategory::Technical->value)
        ->set('priority', TicketPriority::Normal->value)
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->call('submit')
        ->assertSet('show', false)
        ->assertDispatched('ticket-created');

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'subject' => 'Test Issue',
        'category' => TicketCategory::Technical->value,
    ]);
});

test('create ticket validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicketModal::class)
        ->set('subject', '')
        ->set('category', '')
        ->set('message', '')
        ->call('submit')
        ->assertHasErrors(['subject', 'category', 'message']);
});

test('create ticket validates message minimum length', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicketModal::class)
        ->set('subject', 'Test')
        ->set('category', TicketCategory::Technical->value)
        ->set('message', 'Short')
        ->call('submit')
        ->assertHasErrors(['message']);
});

test('customer can view ticket detail', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(MyTickets::class)
        ->call('viewTicket', $ticket->id)
        ->assertSet('selectedTicketId', $ticket->id);
});

test('customer can view ticket detail modal', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertSee($ticket->subject);
});

test('customer cannot view other user tickets', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertForbidden();
});

test('customer can reply to open ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is my reply with enough characters to pass validation.')
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $user->id,
    ]);
});

test('customer cannot reply to closed ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->closed()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is my reply with enough characters.')
        ->call('sendReply')
        ->assertForbidden();
});

test('reply message validates minimum length', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'Short')
        ->call('sendReply')
        ->assertHasErrors(['replyMessage']);
});

test('tickets display correct status badges', function () {
    $user = User::factory()->create();

    $openTicket = SupportTicket::factory()->create([
        'user_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);

    $response = $this->actingAs($user)->get('/support/my-tickets');

    $response->assertSee($openTicket->status->label());
});

test('tickets display correct priority badges', function () {
    $user = User::factory()->create();

    $urgentTicket = SupportTicket::factory()->urgent()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/support/my-tickets');

    $response->assertSee($urgentTicket->priority->label());
});

test('empty state is displayed when no tickets', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/support/my-tickets');

    $response->assertSee('No Support Tickets');
});
