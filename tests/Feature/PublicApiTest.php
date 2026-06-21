<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ServiceCatalogSeeder::class);
        $this->seed(EquipmentTypeSeeder::class);
    }

    public function test_services_endpoint_returns_catalog(): void
    {
        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonCount(5, 'services')
            ->assertJsonPath('services.0.key', '10yd-dumpster'); // ordered by key
    }

    public function test_equipment_endpoint_returns_catalog(): void
    {
        $this->getJson('/api/equipment')
            ->assertOk()
            ->assertJsonCount(6, 'equipment');
    }

    public function test_quote_creates_inquiry_and_applies_default_duration(): void
    {
        $res = $this->postJson('/api/quote', [
            'name' => 'Test Customer',
            'phone' => '(909) 555-7777',
            'email' => 'test@example.com',
            'service_type' => 'junk-removal',
            'zip_code' => '92399',
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertMatchesRegularExpression('/^HAUL-[0-9A-F]{4}$/', $res->json('ref'));

        $inq = Inquiry::first();
        $this->assertSame('Test Customer', $inq->name);
        $this->assertSame(120, $inq->expected_duration_minutes); // junk-removal default
        $this->assertSame('new', $inq->status);
    }

    public function test_quote_honeypot_silently_succeeds_without_creating(): void
    {
        $this->postJson('/api/quote', [
            'name' => 'Bot', 'phone' => '1234567890', 'email' => 'bot@example.com',
            'service_type' => 'other', 'zip_code' => '00000', 'website' => 'http://spam.example',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_lookup_returns_matching_inquiry(): void
    {
        $this->postJson('/api/quote', [
            'name' => 'Jane Doe', 'phone' => '951-555-0199', 'email' => 'jane@example.com',
            'service_type' => 'equipment', 'zip_code' => '92320',
        ])->assertOk();

        $this->getJson('/api/lookup?phone=(951) 555-0199&email=jane@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'inquiries')
            ->assertJsonPath('inquiries.0.name', 'Jane Doe');
    }

    public function test_lookup_requires_phone_and_email(): void
    {
        $this->getJson('/api/lookup?phone=123')->assertStatus(400);
    }
}
