<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\RentalAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalAgreementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAgreement(array $overrides = []): RentalAgreement
    {
        $inquiry = Inquiry::create([
            'name' => 'Sam Rivera', 'phone' => '9095551212', 'email' => 's@e.com',
            'service_type' => 'equipment', 'zip_code' => '92399',
            'equipment_type' => 'Scissor Lift (19-26 ft)', 'quoted_price' => 320,
            'address' => '123 Main St, Yucaipa', 'admin_notes' => 'Gate code 4321',
        ]);

        return RentalAgreement::create(array_merge([
            'inquiry_id' => $inquiry->id,
            'token'      => bin2hex(random_bytes(32)),
            'form_data'  => [],
            'expires_at' => now()->addDays(14)->toISOString(),
        ], $overrides));
    }

    public function test_show_returns_prefill(): void
    {
        $a = $this->makeAgreement();
        $this->getJson("/api/rental-agreement/{$a->token}")
            ->assertOk()
            ->assertJsonPath('inquiry.name', 'Sam Rivera')
            ->assertJsonPath('inquiry.quoted_price', 320)
            ->assertJsonPath('agreement.token', $a->token);
    }

    public function test_show_404_for_unknown_token(): void
    {
        $this->getJson('/api/rental-agreement/nope')->assertStatus(404);
    }

    public function test_sign_persists_and_is_one_time_use(): void
    {
        $a = $this->makeAgreement();

        $this->postJson("/api/rental-agreement/{$a->token}", [
            'form_data' => ['agreed_to_terms' => true, 'signed_name' => 'Sam Rivera', 'pickup_time' => '8:00 AM'],
            'signature_base64' => 'data:image/png;base64,AAAA',
        ])->assertOk()->assertJson(['success' => true]);

        $fresh = $a->fresh();
        $this->assertNotNull($fresh->signed_at);
        $this->assertSame('data:image/png;base64,AAAA', $fresh->signature_base64);
        $this->assertSame('Sam Rivera', $fresh->form_data['signed_name']);

        // One-time use: GET now 410, second sign 410.
        $this->getJson("/api/rental-agreement/{$a->token}")->assertStatus(410)->assertJson(['alreadySigned' => true]);
        $this->postJson("/api/rental-agreement/{$a->token}", [
            'form_data' => ['x' => 1], 'signature_base64' => 'data:image/png;base64,BBBB',
        ])->assertStatus(410);
    }

    public function test_sign_requires_signature(): void
    {
        $a = $this->makeAgreement();
        $this->postJson("/api/rental-agreement/{$a->token}", ['form_data' => ['x' => 1]])->assertStatus(400);
    }

    public function test_expired_link_rejected(): void
    {
        $a = $this->makeAgreement(['expires_at' => now()->subDay()->toISOString()]);
        $this->getJson("/api/rental-agreement/{$a->token}")->assertStatus(410);
    }

    public function test_cancelled_link_rejected(): void
    {
        $a = $this->makeAgreement(['cancelled_at' => now()->toISOString()]);
        $this->getJson("/api/rental-agreement/{$a->token}")->assertStatus(410)->assertJson(['cancelled' => true]);
    }
}
