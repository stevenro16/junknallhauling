<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminInquiryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): static
    {
        $admin = Admin::create([
            'username' => 'boss', 'password_hash' => Hash::make('secret123'), 'must_change_password' => false,
        ]);

        return $this->withSession([
            'admin_id' => $admin->id, 'admin_username' => $admin->username, 'admin_must_change' => false,
        ]);
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $this->actingAdmin()->get('/admin')->assertOk()->assertSee('Quotes');
    }

    public function test_detail_page_renders(): void
    {
        $inq = Inquiry::create(['name' => 'Pat', 'phone' => '9095550000', 'email' => 'p@e.com', 'service_type' => 'other', 'zip_code' => '92399']);
        $this->actingAdmin()->get("/admin/inquiries/{$inq->id}")->assertOk()->assertSee($inq->ref);
    }

    public function test_store_creates_inquiry(): void
    {
        $this->actingAdmin()->postJson('/admin/api/inquiries', ['phone' => '9095551111', 'name' => 'New Lead'])
            ->assertStatus(201)
            ->assertJsonPath('inquiry.service_type', 'other');

        $this->assertSame(1, Inquiry::where('phone', '9095551111')->count());
    }

    public function test_store_requires_phone(): void
    {
        $this->actingAdmin()->postJson('/admin/api/inquiries', ['name' => 'No Phone'])->assertStatus(400);
    }

    public function test_counts_buckets(): void
    {
        foreach (['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed'] as $s) {
            Inquiry::create(['name' => $s, 'phone' => '1', 'email' => 'a@b.com', 'service_type' => 'other', 'zip_code' => '1', 'status' => $s]);
        }

        $this->actingAdmin()->getJson('/admin/api/inquiries/counts')->assertOk()->assertJson([
            'new' => 3, 'scheduled' => 1, 'pendingPayment' => 1, 'workqueueTotal' => 5,
        ]);
    }

    public function test_update_geocodes_address_and_logs_status(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '34.0336', 'lon' => '-117.0436']], 200),
        ]);

        $inq = Inquiry::create(['name' => 'Geo', 'phone' => '9095552222', 'email' => 'g@e.com', 'service_type' => 'other', 'zip_code' => '92399', 'status' => 'new']);

        $this->actingAdmin()->patchJson("/admin/api/inquiries/{$inq->id}", [
            'status' => 'reviewing',
            'address' => '123 Main St, Yucaipa CA',
        ])->assertOk();

        $fresh = $inq->fresh();
        $this->assertEqualsWithDelta(34.0336, $fresh->latitude, 0.001);
        $this->assertEqualsWithDelta(-117.0436, $fresh->longitude, 0.001);
        $this->assertSame('reviewing', $fresh->status);
        $this->assertSame(1, $fresh->statusHistory()->where('new_status', 'reviewing')->count());
    }

    public function test_update_clears_coords_when_address_emptied(): void
    {
        $inq = Inquiry::create(['name' => 'X', 'phone' => '1', 'email' => 'a@b.com', 'service_type' => 'other', 'zip_code' => '1', 'latitude' => 34, 'longitude' => -117]);

        $this->actingAdmin()->patchJson("/admin/api/inquiries/{$inq->id}", ['address' => ''])->assertOk();

        $this->assertNull($inq->fresh()->latitude);
    }

    public function test_audit_logs_event(): void
    {
        $inq = Inquiry::create(['name' => 'A', 'phone' => '1', 'email' => 'a@b.com', 'service_type' => 'other', 'zip_code' => '1']);

        $this->actingAdmin()->postJson("/admin/api/inquiries/{$inq->id}/audit", ['action' => 'Quote Verified'])->assertOk();

        $this->assertSame(1, $inq->statusHistory()->where('new_status', 'Quote Verified')->count());
    }

    public function test_guest_blocked_from_admin_api(): void
    {
        $this->getJson('/admin/api/inquiries/counts')->assertStatus(401);
    }
}
