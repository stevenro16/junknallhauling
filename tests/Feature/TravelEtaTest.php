<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TravelEtaTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): static
    {
        $a = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $a->id, 'admin_username' => $a->username, 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    private function makeInquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com',
            'service_type' => 'junk-removal', 'zip_code' => '92399', 'status' => 'scheduled',
            'address' => '5036 Oak St, Mentone CA 92359', 'latitude' => 34.05, 'longitude' => -117.12,
        ], $overrides));
    }

    public function test_field_eta_returns_drive_estimate(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response(['routes' => [['duration' => 600, 'distance' => 8500]]], 200),
        ]);
        $inq = $this->makeInquiry();

        $this->adminSession()->postJson("/admin/field/job/{$inq->id}/eta", ['lat' => 34.02, 'lng' => -117.18])
            ->assertOk()
            ->assertJsonPath('duration_minutes', 10)
            ->assertJsonPath('distance_miles', 5.3);
    }

    public function test_eta_requires_current_location(): void
    {
        $inq = $this->makeInquiry();

        $this->adminSession()->postJson("/admin/field/job/{$inq->id}/eta", [])
            ->assertStatus(422);
    }

    public function test_eta_needs_a_mappable_address(): void
    {
        $inq = $this->makeInquiry(['address' => null, 'latitude' => null, 'longitude' => null]);

        $this->adminSession()->postJson("/admin/field/job/{$inq->id}/eta", ['lat' => 34.0, 'lng' => -117.1])
            ->assertStatus(422);
    }
}
