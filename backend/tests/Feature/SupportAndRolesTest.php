<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessageResource;
use App\Models\{AdminRole, AdminUser, ContactMessage, SupportTicket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Mail};
use Laravel\Passport\Passport;
use Tests\TestCase;

class SupportAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_reply_reaches_the_customer(): void
    {
        Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED']]], 200)]);
        $user = User::where('phone', '01711000001')->firstOrFail();
        Passport::actingAs($user);
        $id = $this->postJson('/api/v1/support', ['subject' => 'Late', 'category' => 'order', 'message' => 'Where is it?'])
            ->assertStatus(201)->json('data.id');

        $ticket = SupportTicket::findOrFail($id);
        $ticket->addAdminReply('It ships today.', AdminUser::first()->id);

        $this->assertSame('in_progress', $ticket->fresh()->status);
        $this->assertSame(1, $user->notifications()->where('data->kind', 'support_reply')->count());

        $msgs = $this->getJson("/api/v1/support/{$id}")->assertOk()->json('data.messages');
        $this->assertCount(2, $msgs);
        $this->assertSame('admin', $msgs[1]['sender_type']);
        $this->assertTrue(\App\Models\SmsLog::where('message', 'like', '%' . $ticket->ticket_number . '%')->exists(), 'SMS sent from the template');
    }

    public function test_contact_reply_by_sms_and_email_marks_resolved(): void
    {
        Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED']]], 200)]);
        Mail::fake();
        $this->actingAs(AdminUser::first(), 'admin');
        $m = ContactMessage::create(['name' => 'Rahim', 'phone' => '01711111111', 'email' => 'r@example.com',
            'subject' => 'Refund?', 'message' => 'Where is my refund?', 'status' => 'open']);

        $sent = ContactMessageResource::sendReply($m, 'both', 'Refund sent today.');

        $this->assertSame(['SMS', 'Email'], $sent);
        $m->refresh();
        $this->assertSame('resolved', $m->status);
        $this->assertSame('Refund sent today.', $m->reply);
        $this->assertNotNull($m->replied_at);
    }

    public function test_failed_reply_keeps_message_open(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $m = ContactMessage::create(['name' => 'A', 'phone' => '01711111111', 'subject' => 'x', 'message' => 'y', 'status' => 'open']);
        $this->assertSame([], ContactMessageResource::sendReply($m, 'sms', 'hello'));
        $this->assertSame('open', $m->fresh()->status);
        $this->assertNull($m->fresh()->reply);
    }

    public function test_role_limits_which_admin_pages_open(): void
    {
        $manager = AdminUser::where('email', 'manager@example.com')->firstOrFail();   // seeded as "Order Manager"
        $this->assertFalse($manager->isSuper());
        $this->assertTrue($manager->canArea('orders'));
        $this->assertFalse($manager->canArea('settings'));

        $this->actingAs($manager, 'admin');
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/settings-page')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();
    }

    public function test_admin_without_a_role_has_full_access(): void
    {
        $super = AdminUser::where('email', '!=', 'manager@example.com')->firstOrFail();   // no role = full access
        $this->assertTrue($super->isSuper());
        $this->actingAs($super, 'admin');
        $this->get('/admin/admin-users')->assertOk();
        $this->get('/admin/admin-roles')->assertOk();
        $this->get('/admin/settings-page')->assertOk();
    }

    public function test_super_role_and_custom_role(): void
    {
        $role = AdminRole::create(['name' => 'Only Catalog', 'permissions' => ['catalog']]);
        $a = new AdminUser(['name' => 'x', 'email' => 'x@example.com', 'role_id' => $role->id]);
        $a->setRelation('role', $role);
        $this->assertTrue($a->canArea('catalog'));
        $this->assertFalse($a->canArea('orders'));
        $this->assertTrue(AdminRole::where('is_super', true)->exists());
    }
}
