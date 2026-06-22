<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CancelQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_persists_cancelled_status(): void
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        // A quick-created quote: blank name/email, service_type 'other'.
        $inq = Inquiry::create(['name' => '', 'phone' => '9095550000', 'email' => '', 'service_type' => 'other', 'status' => 'scheduled']);

        // Mirrors handleConfirmCancel(): buildBody sends null for the blank fields.
        $this->withSession(['admin_id' => $admin->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false])
            ->patchJson("/admin/api/inquiries/{$inq->id}", [
                'status' => 'cancelled', 'name' => '', 'phone' => '9095550000',
                'email' => null, 'service_type' => null, 'assigned_employee_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('inquiry.status', 'cancelled');

        $this->assertSame('cancelled', $inq->fresh()->status);
        $this->assertSame('', $inq->fresh()->email);   // null coerced to '' (NOT NULL column)
    }
}
