<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\EquipmentType;
use App\Models\ServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): static
    {
        $admin = Admin::create(['username' => 'boss', 'password_hash' => Hash::make('secret123'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $admin->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    public function test_service_crud(): void
    {
        $a = $this->actingAdmin();

        // Key is derived from the service name (admin no longer provides one).
        $a->postJson('/admin/api/services', ['label' => 'Junk Removal', 'default_price' => 375, 'default_duration_minutes' => 120])
            ->assertStatus(201);
        $svc = ServiceCatalog::first();
        $this->assertSame('junk-removal', $svc->key);

        // A same-named service gets a unique key rather than colliding.
        $a->postJson('/admin/api/services', ['label' => 'Junk Removal'])->assertStatus(201);
        $this->assertTrue(ServiceCatalog::where('key', 'junk-removal-2')->exists());
        $this->assertSame(2, ServiceCatalog::where('label', 'Junk Removal')->count());

        // A name is required.
        $a->postJson('/admin/api/services', ['label' => ''])->assertStatus(400);

        $a->patchJson("/admin/api/services/{$svc->id}", ['default_price' => 400])->assertOk();
        $this->assertEquals(400.0, $svc->fresh()->default_price);

        // Customer instructions (for later workflows).
        $a->patchJson("/admin/api/services/{$svc->id}", ['customer_instructions' => 'Bring a gate key'])->assertOk();
        $this->assertSame('Bring a gate key', $svc->fresh()->customer_instructions);

        // Customer visibility toggle (separate from active; visible by default).
        $this->assertTrue($svc->fresh()->customer_visible);
        $a->patchJson("/admin/api/services/{$svc->id}", ['customer_visible' => false])->assertOk();
        $this->assertFalse($svc->fresh()->customer_visible);

        $a->deleteJson("/admin/api/services/{$svc->id}")->assertOk();
        $this->assertNull($svc->fresh()); // permanent delete
    }

    public function test_equipment_crud(): void
    {
        $a = $this->actingAdmin();

        $a->postJson('/admin/api/equipment', ['name' => 'Scissor Lift', 'avg_cost_per_hour' => 85])->assertStatus(201);
        $eq = EquipmentType::first();
        $a->postJson('/admin/api/equipment', ['name' => 'Scissor Lift'])->assertStatus(409);

        $a->patchJson("/admin/api/equipment/{$eq->id}", ['daily_rate' => 600])->assertOk();
        $this->assertEquals(600.0, $eq->fresh()->daily_rate);

        // Customer instructions (for later workflows).
        $a->patchJson("/admin/api/equipment/{$eq->id}", ['customer_instructions' => 'Operator must be certified'])->assertOk();
        $this->assertSame('Operator must be certified', $eq->fresh()->customer_instructions);

        // Customer visibility toggle (separate from active; visible by default).
        $this->assertTrue($eq->fresh()->customer_visible);
        $a->patchJson("/admin/api/equipment/{$eq->id}", ['customer_visible' => false])->assertOk();
        $this->assertFalse($eq->fresh()->customer_visible);

        $a->deleteJson("/admin/api/equipment/{$eq->id}")->assertOk();
        $this->assertNull($eq->fresh()); // permanent delete
    }

    public function test_flat_rate_equipment_pricing(): void
    {
        $a = $this->actingAdmin();

        $a->postJson('/admin/api/equipment', [
            'name' => 'Oversized 10-Yard Dump Trailer',
            'flat_price' => 349, 'included_days' => 7, 'included_tons' => 1,
            'price_per_additional_ton' => 84, 'price_per_additional_day' => 15,
        ])->assertStatus(201);

        $eq = EquipmentType::where('name', 'Oversized 10-Yard Dump Trailer')->first();
        $this->assertTrue($eq->isFlatRate());

        // Estimate: base within the included days; base + extra days beyond.
        $this->assertEquals(349.0, $eq->flatRateEstimate(7));
        $this->assertEquals(349.0, $eq->flatRateEstimate(3));
        $this->assertEquals(349.0 + 3 * 15, $eq->flatRateEstimate(10));

        // Exposed to the public equipment API.
        $json = $a->getJson('/api/equipment')->assertOk()->json('equipment.0');
        $this->assertEquals(349, $json['flat_price']);
        $this->assertEquals(7, $json['included_days']);
        $this->assertEquals(84, $json['price_per_additional_ton']);

        // Editable via update.
        $a->patchJson("/admin/api/equipment/{$eq->id}", ['price_per_additional_ton' => 90])->assertOk();
        $this->assertEquals(90.0, $eq->fresh()->price_per_additional_ton);

        // A plain hourly item is not flat-rate.
        $hourly = EquipmentType::create(['name' => 'Lift', 'avg_cost_per_hour' => 85]);
        $this->assertFalse($hourly->isFlatRate());
        $this->assertNull($hourly->flatRateEstimate(5));
    }

    public function test_calendar_page_renders(): void
    {
        $this->actingAdmin()->get('/admin/calendar')->assertOk()->assertSee('Schedule');
    }

    public function test_catalog_requires_auth(): void
    {
        $this->getJson('/admin/api/services')->assertStatus(401);
    }
}
