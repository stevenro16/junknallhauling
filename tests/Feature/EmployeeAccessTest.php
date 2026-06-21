<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('secret123'), 'must_change_password' => false]);
    }

    private function employee(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'username' => 'emp', 'role' => 'employee', 'email' => 'emp@example.com',
            'password_hash' => Hash::make('secret123'), 'must_change_password' => false,
        ], $overrides));
    }

    private function sessionFor(Admin $a): static
    {
        return $this->withSession([
            'admin_id' => $a->id, 'admin_username' => $a->username,
            'admin_role' => $a->role, 'admin_must_change' => $a->must_change_password,
        ]);
    }

    private function makeInquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com',
            'service_type' => 'junk-removal', 'zip_code' => '92399', 'status' => 'scheduled',
            'confirmed_date_time' => '2026-06-22T09:00',
        ], $overrides));
    }

    public function test_employee_is_blocked_from_admin_pages_and_apis(): void
    {
        $emp = $this->employee();

        $this->sessionFor($emp)->get('/admin')->assertRedirect(route('admin.my-schedule'));
        $this->sessionFor($emp)->getJson('/admin/api/inquiries')->assertStatus(403);
        $this->sessionFor($emp)->get('/admin/my-schedule')->assertOk();
    }

    public function test_employee_only_sees_and_updates_assigned_jobs(): void
    {
        $emp = $this->employee();
        $other = $this->employee(['username' => 'emp2', 'email' => 'e2@example.com']);
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id]);
        $theirs = $this->makeInquiry(['phone' => '9095551111', 'assigned_employee_id' => $other->id]);

        $this->sessionFor($emp)->get("/admin/my-schedule/job/{$mine->id}")->assertOk();
        $this->sessionFor($emp)->get("/admin/my-schedule/job/{$theirs->id}")->assertNotFound();

        // Allowed forward status update is applied + logged.
        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$mine->id}/status", ['status' => 'service_performed'])->assertRedirect();
        $this->assertSame('service_performed', $mine->fresh()->status);
        $this->assertSame(1, $mine->statusHistory()->where('new_status', 'service_performed')->count());

        // A disallowed status is rejected (unchanged).
        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$mine->id}/status", ['status' => 'cancelled']);
        $this->assertSame('service_performed', $mine->fresh()->status);
    }

    public function test_admin_creates_employee_and_assigns_a_quote(): void
    {
        $admin = $this->admin();

        $this->sessionFor($admin)->postJson('/admin/admins', ['username' => 'newemp', 'password' => 'model123!', 'role' => 'employee'])
            ->assertStatus(201);
        $emp = Admin::where('username', 'newemp')->first();
        $this->assertSame('employee', $emp->role);
        $this->assertTrue($emp->must_change_password);

        $inq = $this->makeInquiry();
        $this->sessionFor($admin)->patchJson("/admin/api/inquiries/{$inq->id}", ['assigned_employee_id' => $emp->id])->assertOk();
        $this->assertSame($emp->id, $inq->fresh()->assigned_employee_id);

        $this->sessionFor($admin)->patchJson("/admin/api/inquiries/{$inq->id}", ['assigned_employee_id' => null])->assertOk();
        $this->assertNull($inq->fresh()->assigned_employee_id);
    }

    public function test_employee_records_arrival_departure_and_signature(): void
    {
        $emp = $this->employee();
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id, 'status' => 'service_performed']);

        // Arrival + departure stamps (scoped to the assigned visit).
        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$mine->id}/time/arrival")->assertRedirect();
        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$mine->id}/time/departure")->assertRedirect();
        $fresh = $mine->fresh();
        $this->assertNotNull($fresh->arrived_at);
        $this->assertNotNull($fresh->departed_at);

        // Signature marks the service performed (ready for the admin to bill).
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/sign", ['signature' => $png])
            ->assertOk()->assertJson(['success' => true]);
        $fresh = $mine->fresh();
        $this->assertSame($png, $fresh->service_signature);
        $this->assertNotNull($fresh->service_signed_at);
        $this->assertSame('service_performed', $fresh->status);
    }

    public function test_signature_from_scheduled_advances_to_service_performed(): void
    {
        $emp = $this->employee();
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id, 'status' => 'scheduled']);

        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/sign", ['signature' => $png])->assertOk();

        $this->assertSame('service_performed', $mine->fresh()->status);
        $this->assertSame(1, $mine->statusHistory()->where('new_status', 'service_performed')->count());
    }

    public function test_sign_rejects_non_image_and_guards_ownership(): void
    {
        $emp = $this->employee();
        $other = $this->employee(['username' => 'emp2', 'email' => 'e2@example.com']);
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id, 'status' => 'service_performed']);
        $theirs = $this->makeInquiry(['phone' => '9095551111', 'assigned_employee_id' => $other->id]);

        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/sign", ['signature' => 'not-an-image'])
            ->assertStatus(422);
        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$theirs->id}/time/arrival")->assertNotFound();
    }

    public function test_employee_cannot_mark_a_job_completed(): void
    {
        $emp = $this->employee();
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id, 'status' => 'service_performed']);

        $this->sessionFor($emp)->post("/admin/my-schedule/job/{$mine->id}/status", ['status' => 'completed']);

        $this->assertSame('service_performed', $mine->fresh()->status);
        $this->assertSame(0, $mine->statusHistory()->where('new_status', 'completed')->count());
    }

    public function test_employee_adds_internal_and_customer_visible_comments(): void
    {
        $emp = $this->employee();
        $mine = $this->makeInquiry(['assigned_employee_id' => $emp->id]);
        $theirs = $this->makeInquiry(['phone' => '9095551111']); // unassigned

        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/comment", ['body' => 'Gate code 1234'])
            ->assertOk()->assertJsonPath('comment.customer_visible', false);
        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/comment", ['body' => 'On our way!', 'customer_visible' => true])
            ->assertOk()->assertJsonPath('comment.customer_visible', true);

        $this->assertSame(2, $mine->fresh()->comments()->count());

        // Empty body rejected; commenting on an unowned job is 404.
        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$mine->id}/comment", ['body' => '  '])->assertStatus(422);
        $this->sessionFor($emp)->postJson("/admin/my-schedule/job/{$theirs->id}/comment", ['body' => 'x'])->assertNotFound();
    }

    public function test_customer_lookup_exposes_only_customer_visible_comments(): void
    {
        $inq = $this->makeInquiry(['phone' => '9095559999', 'email' => 'cust@e.com']);
        $inq->comments()->create(['author_name' => 'emp', 'body' => 'INTERNAL crew note', 'customer_visible' => false]);
        $inq->comments()->create(['author_name' => 'emp', 'body' => 'See you at 9am', 'customer_visible' => true]);

        $res = $this->getJson('/api/lookup?phone=9095559999&email=cust@e.com')->assertOk();

        $comments = $res->json('inquiries.0.comments');
        $this->assertCount(1, $comments);
        $this->assertSame('See you at 9am', $comments[0]['body']);
        $res->assertDontSee('INTERNAL crew note');
    }

    public function test_employee_first_password_change_requires_email(): void
    {
        $emp = $this->employee(['email' => null, 'must_change_password' => true]);

        $this->sessionFor($emp)->postJson('/admin/change-password', ['newPassword' => 'newpass1'])->assertStatus(400);

        $this->sessionFor($emp)->postJson('/admin/change-password', ['newPassword' => 'newpass1', 'email' => 'emp@field.com'])->assertOk();
        $emp->refresh();
        $this->assertSame('emp@field.com', $emp->email);
        $this->assertFalse($emp->must_change_password);
    }
}
