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
use App\Notifications\TicketAssigned;
use App\Notifications\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Access Control Tests
test('admin can access support tickets list page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/support/tickets');

    $response->assertSuccessful();
    $response->assertSeeLivewire(SupportTicketsList::class);
});

test('non-admin cannot access support tickets list page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/support/tickets');

    $response->assertForbidden();
});

test('guest is redirected to login from support tickets page', function () {
    $response = $this->get('/admin/support/tickets');

    $response->assertRedirect(route('login'));
});

// Ticket List Tests
test('admin can see all tickets', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $ticket1 = SupportTicket::factory()->create(['user_id' => $user1->id]);
    $ticket2 = SupportTicket::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($admin)->get('/admin/support/tickets');

    $response->assertSee($ticket1->subject);
    $response->assertSee($ticket2->subject);
});

test('admin can filter tickets by open status', function () {
    $admin = User::factory()->admin()->create();

    $openTicket = SupportTicket::factory()->create();
    $closedTicket = SupportTicket::factory()->closed()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'open')
        ->assertSee($openTicket->subject)
        ->assertDontSee($closedTicket->subject);
});

test('admin can filter tickets by closed status', function () {
    $admin = User::factory()->admin()->create();

    $openTicket = SupportTicket::factory()->create();
    $closedTicket = SupportTicket::factory()->closed()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'closed')
        ->assertDontSee($openTicket->subject)
        ->assertSee($closedTicket->subject);
});

test('admin can filter tickets by unassigned status', function () {
    $admin = User::factory()->admin()->create();

    $assignedTicket = SupportTicket::factory()->assigned()->create();
    $unassignedTicket = SupportTicket::factory()->create(['assigned_to' => null]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'unassigned')
        ->assertSee($unassignedTicket->subject)
        ->assertDontSee($assignedTicket->subject);
});

test('admin can filter tickets needing first response', function () {
    $admin = User::factory()->admin()->create();

    $needsResponse = SupportTicket::factory()->create(['first_response_at' => null]);
    $hasResponse = SupportTicket::factory()->create(['first_response_at' => now()]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'needs_response')
        ->assertSee($needsResponse->subject)
        ->assertDontSee($hasResponse->subject);
});

test('admin can filter tickets by category', function () {
    $admin = User::factory()->admin()->create();

    $technicalTicket = SupportTicket::factory()->technical()->create();
    $billingTicket = SupportTicket::factory()->billing()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterCategory', TicketCategory::Technical->value)
        ->assertSee($technicalTicket->subject)
        ->assertDontSee($billingTicket->subject);
});

test('admin can filter tickets by priority', function () {
    $admin = User::factory()->admin()->create();

    $urgentTicket = SupportTicket::factory()->urgent()->create();
    $lowTicket = SupportTicket::factory()->low()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterPriority', TicketPriority::Urgent->value)
        ->assertSee($urgentTicket->subject)
        ->assertDontSee($lowTicket->subject);
});

test('admin can filter tickets assigned to them', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $myTicket = SupportTicket::factory()->create(['assigned_to' => $admin->id]);
    $otherTicket = SupportTicket::factory()->create(['assigned_to' => $otherAdmin->id]);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('filterAssignment', 'mine')
        ->assertSee($myTicket->subject)
        ->assertDontSee($otherTicket->subject);
});

test('admin can search tickets by subject', function () {
    $admin = User::factory()->admin()->create();

    $ticket1 = SupportTicket::factory()->create(['subject' => 'Login Problem']);
    $ticket2 = SupportTicket::factory()->create(['subject' => 'Billing Issue']);

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('search', 'Login')
        ->assertSee($ticket1->subject)
        ->assertDontSee($ticket2->subject);
});

test('admin can search tickets by customer email', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'findme@example.com']);

    $ticket1 = SupportTicket::factory()->create(['user_id' => $user->id]);
    $ticket2 = SupportTicket::factory()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('search', 'findme@example')
        ->assertSee($ticket1->subject)
        ->assertDontSee($ticket2->subject);
});

test('tickets are paginated at 20 per page', function () {
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->count(25)->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->perPage() === 20 && $tickets->total() === 25;
        });
});

// Ticket Detail Modal Tests
test('admin can view any ticket detail', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertSee($ticket->subject)
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('admin can reply to ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is an admin reply with enough characters to pass validation.')
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'is_internal_note' => false,
    ]);
});

test('admin can add internal notes', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is an internal note for other admins to see.')
        ->set('isInternalNote', true)
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'is_internal_note' => true,
    ]);
});

test('internal notes are not visible to customers', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    // Admin adds internal note
    SupportMessage::factory()->create([
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'message' => 'Secret internal note content',
        'is_internal_note' => true,
    ]);

    // Admin adds public reply
    SupportMessage::factory()->create([
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'message' => 'Public reply content visible to customer',
        'is_internal_note' => false,
    ]);

    // Customer should only see public messages
    Livewire::actingAs($user)
        ->test(\App\Livewire\Customer\TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->assertSee('Public reply content visible to customer')
        ->assertDontSee('Secret internal note content');
});

test('admin can change ticket status', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['status' => TicketStatus::Open]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::InProgress->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::InProgress->value,
    ]);

    Notification::assertSentTo($ticket->user, TicketStatusChanged::class);
});

test('changing status to resolved sets resolved_at timestamp', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create([
        'status' => TicketStatus::Open,
        'resolved_at' => null,
    ]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::Resolved->value)
        ->call('updateStatus');

    $ticket->refresh();
    expect($ticket->resolved_at)->not->toBeNull();
});

test('changing status to closed sets closed_at timestamp', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create([
        'status' => TicketStatus::Open,
        'closed_at' => null,
    ]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('newStatus', TicketStatus::Closed->value)
        ->call('updateStatus');

    $ticket->refresh();
    expect($ticket->closed_at)->not->toBeNull();
});

test('admin can assign ticket to another admin', function () {
    $admin = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => null]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', $targetAdmin->id)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'assigned_to' => $targetAdmin->id,
    ]);

    Notification::assertSentTo($targetAdmin, TicketAssigned::class);
});

test('admin can assign ticket to self', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => null]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', $admin->id)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'assigned_to' => $admin->id,
    ]);
});

test('admin can unassign ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('assignToUserId', null)
        ->call('assignTicket')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'assigned_to' => null,
    ]);
});

test('reply message validates minimum length', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'Short')
        ->call('sendReply')
        ->assertHasErrors(['replyMessage']);
});

test('first admin response sets first_response_at timestamp', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'user_id' => $user->id,
        'first_response_at' => null,
    ]);

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'This is the first admin response to this ticket.')
        ->call('sendReply');

    $ticket->refresh();
    expect($ticket->first_response_at)->not->toBeNull();
});

test('admin can still add messages to closed tickets', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->closed()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(TicketDetailModal::class, ['ticketId' => $ticket->id])
        ->set('replyMessage', 'Admin adding note to closed ticket.')
        ->set('isInternalNote', true)
        ->call('sendReply')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
    ]);
});

test('empty state is displayed when no tickets match filters', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('search', 'nonexistentticket12345')
        ->assertSee('No Tickets Found');
});

test('admin can view ticket from list', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->call('viewTicket', $ticket->id)
        ->assertSet('selectedTicketId', $ticket->id);
});

test('admin can close ticket detail modal', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create();

    Livewire::actingAs($admin)
        ->test(SupportTicketsList::class)
        ->set('filterStatus', 'all')
        ->set('selectedTicketId', $ticket->id)
        ->call('closeTicketDetail')
        ->assertSet('selectedTicketId', null);
});
