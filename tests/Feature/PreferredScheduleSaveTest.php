<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PreferredScheduleSaveTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): static
    {
        $a = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $a->id, 'admin_username' => $a->username, 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    public function test_update_persists_multi_value_preferred_day_and_time(): void
    {
        $inq = Inquiry::create(['name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com', 'service_type' => 'junk-removal', 'zip_code' => '92399', 'status' => 'new']);

        $res = $this->adminSession()->patchJson("/admin/api/inquiries/{$inq->id}", [
            'preferred_day' => 'Monday, Wednesday',
            'preferred_time' => 'Morning (8am - 12pm), Evening (5pm - 8pm)',
        ]);

        $res->assertOk();
        $fresh = $inq->fresh();
        $this->assertSame('Monday, Wednesday', $fresh->preferred_day);
        $this->assertSame('Morning (8am - 12pm), Evening (5pm - 8pm)', $fresh->preferred_time);

        // The JSON response (which the front-end re-hydrates from) carries them back.
        $res->assertJsonPath('inquiry.preferred_day', 'Monday, Wednesday');
        $res->assertJsonPath('inquiry.preferred_time', 'Morning (8am - 12pm), Evening (5pm - 8pm)');
    }
}
