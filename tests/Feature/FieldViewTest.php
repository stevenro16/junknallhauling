<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Payment Link');
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

    public function test_employee_cannot_reach_field_view(): void
    {
        $emp = $this->employee();

        $this->sessionFor($emp)->get('/admin/field')->assertRedirect(route('admin.my-schedule'));
    }
}
