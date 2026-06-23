<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FieldViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('secret123'), 'must_change_password' => false]);
    }

    private function employee(): Admin
    {
        return Admin::create([
            'username' => 'emp', 'role' => 'employee', 'email' => 'emp@example.com',
            'password_hash' => Hash::make('secret123'), 'must_change_password' => false,
        ]);
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

    public function test_admin_sees_all_scheduled_jobs_in_field_view(): void
    {
        $admin = $this->admin();
        $this->makeInquiry(['name' => 'Alice']);
        $this->makeInquiry(['phone' => '9095551111', 'name' => 'Bob']);

        $this->sessionFor($admin)->get('/admin/field')->assertOk()->assertSee('Field View');
    }

    public function test_field_job_sheet_renders_with_admin_extras(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry(['quoted_price' => 250]);

        $this->sessionFor($admin)->get("/admin/field/job/{$inq->id}")
            ->assertOk()
            ->assertSee('Open full quote')
            ->assertSee('Amount due')
            ->assertSee('Mark Paid');
    }

    public function test_field_signature_marks_service_performed(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();

        $this->sessionFor($admin)->postJson("/admin/field/job/{$inq->id}/sign", [
            'signature' => 'data:image/png;base64,iVBORw0KGgo=',
        ])->assertOk();

        $this->assertSame('service_performed', $inq->fresh()->status);
    }

    public function test_admin_can_manually_set_any_status_from_field_view(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();

        $this->sessionFor($admin)->post("/admin/field/job/{$inq->id}/status", ['status' => 'cancelled'])
            ->assertRedirect(route('admin.field.job', $inq->id));

        $this->assertSame('cancelled', $inq->fresh()->status);
    }

    public function test_admin_records_in_field_payment_and_settles_open_link(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry(['quoted_price' => 300]);
        $link = $inq->paymentLinks()->create(['token' => 'tok-'.$inq->id, 'amount' => 300]);

        $this->sessionFor($admin)->post("/admin/field/job/{$inq->id}/payment", ['payment_method' => 'Cash'])
            ->assertOk()
            ->assertJsonPath('payment_method', 'Cash');

        $this->assertSame('Cash', $inq->fresh()->payment_method);
        $this->assertNotNull($inq->fresh()->payment_date);
        // The open link is settled so it doesn't linger as "awaiting payment".
        $this->assertNotNull($link->fresh()->paid_at);
    }

    public function test_field_payment_can_set_amount_when_none_was_quoted(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();   // no quoted_price

        $this->sessionFor($admin)->postJson("/admin/field/job/{$inq->id}/payment", [
            'payment_method' => 'Cash', 'amount' => 175.5,
        ])->assertOk()->assertJsonPath('quoted_price', 175.5);

        $this->assertEquals(175.5, $inq->fresh()->quoted_price);
    }

    public function test_field_payment_link_can_set_amount_when_none_was_quoted(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();   // no quoted_price

        $this->sessionFor($admin)->postJson("/admin/api/inquiries/{$inq->id}/payment-link", ['amount' => 200])
            ->assertOk()
            ->assertJsonPath('payment_link.amount', 200);

        $this->assertEquals(200, $inq->fresh()->quoted_price);
    }

    public function test_field_view_captures_and_removes_arrival_photos(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();

        $this->sessionFor($admin)->post("/admin/field/job/{$inq->id}/photo/arrival", [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])->assertRedirect(route('admin.field.job', $inq->id));

        $this->assertCount(2, $inq->fresh()->arrival_photos);
        $this->assertStringStartsWith('data:image/', $inq->fresh()->arrival_photos[0]);

        // Remove the first one.
        $this->sessionFor($admin)->post("/admin/field/job/{$inq->id}/photo/arrival/remove", ['index' => 0])
            ->assertRedirect(route('admin.field.job', $inq->id));
        $this->assertCount(1, $inq->fresh()->arrival_photos);
        $this->assertEmpty($inq->fresh()->departure_photos ?? []);
    }

    public function test_field_payment_requires_a_method(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry();

        $this->sessionFor($admin)->postJson("/admin/field/job/{$inq->id}/payment", ['payment_method' => ''])
            ->assertStatus(422);
    }

    public function test_signature_with_status_records_that_action(): void
    {
        $admin = $this->admin();
        $inq = $this->makeInquiry(['service_type' => 'equipment', 'equipment_type' => 'Boom Lift']);

        $this->sessionFor($admin)->postJson("/admin/field/job/{$inq->id}/sign", [
            'signature' => 'data:image/png;base64,iVBORw0KGgo=',
            'status' => 'equipment_delivered',
        ])->assertOk();

        $fresh = $inq->fresh();
        $this->assertSame('equipment_delivered', $fresh->status);
        $this->assertArrayHasKey('equipment_delivered', $fresh->signatures);
    }

    public function test_employee_cannot_reach_field_view(): void
    {
        $emp = $this->employee();

        $this->sessionFor($emp)->get('/admin/field')->assertRedirect(route('admin.my-schedule'));
    }
}
