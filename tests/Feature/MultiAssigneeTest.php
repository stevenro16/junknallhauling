<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiAssigneeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $name): Admin
    {
        return Admin::create(['username' => $name, 'role' => 'employee', 'email' => "$name@e.com", 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
    }

    private function sessionFor(Admin $a): static
    {
        return $this->withSession(['admin_id' => $a->id, 'admin_username' => $a->username, 'admin_role' => $a->role, 'admin_must_change' => false]);
    }

    private function inquiry(array $o = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com', 'service_type' => 'junk-removal',
            'zip_code' => '92399', 'status' => 'scheduled', 'confirmed_date_time' => '2026-06-23T09:00',
        ], $o));
    }

    public function test_update_persists_multiple_assignees_and_syncs_primary(): void
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        $a = $this->employee('alice');
        $b = $this->employee('bob');
        $inq = $this->inquiry();

        $this->sessionFor($admin)->patchJson("/admin/api/inquiries/{$inq->id}", [
            'assigned_employee_ids' => [$a->id, $b->id],
        ])->assertOk();

        $fresh = $inq->fresh();
        $this->assertSame([$a->id, $b->id], $fresh->assigned_employee_ids);
        $this->assertSame($a->id, $fresh->assigned_employee_id);   // legacy single synced to primary
    }

    public function test_secondary_assignee_can_open_their_job(): void
    {
        $a = $this->employee('alice');
        $b = $this->employee('bob');
        $c = $this->employee('carol');
        $inq = $this->inquiry(['assigned_employee_id' => $a->id, 'assigned_employee_ids' => [$a->id, $b->id]]);

        $this->sessionFor($a)->get("/admin/my-schedule/job/{$inq->id}")->assertOk();
        $this->sessionFor($b)->get("/admin/my-schedule/job/{$inq->id}")->assertOk();   // secondary
        $this->sessionFor($c)->get("/admin/my-schedule/job/{$inq->id}")->assertNotFound();
    }
}
