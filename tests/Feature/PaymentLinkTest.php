<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): static
    {
        $admin = Admin::create(['username' => 'boss', 'password_hash' => Hash::make('secret123'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $admin->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    private function makeInquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Pat Doe', 'phone' => '9095550000', 'email' => 'pat@example.com',
            'service_type' => 'junk-removal', 'zip_code' => '92399',
        ], $overrides));
    }

    public function test_full_payment_link_flow(): void
    {
        $a = $this->actingAdmin();
        $inq = $this->makeInquiry(['quoted_price' => 250]);

        // Admin generates a link for the quoted price.
        $gen = $a->postJson("/admin/api/inquiries/{$inq->id}/payment-link")->assertOk();
        $token = $gen->json('payment_link.token');
        $this->assertEquals(250, $gen->json('payment_link.amount'));

        // Public (stateless) page data.
        $show = $this->getJson("/api/payment/{$token}")->assertOk();
        $this->assertEquals(250, $show->json('payment.amount'));
        $this->assertSame($inq->ref, $show->json('inquiry.ref'));

        // Customer pays.
        $this->postJson("/api/payment/{$token}")->assertOk()->assertJson(['success' => true]);

        // Inquiry now reflects the payment.
        $this->assertSame('Online Payment', $inq->fresh()->payment_method);

        // One-time use — the link is spent.
        $this->getJson("/api/payment/{$token}")->assertStatus(410);
    }

    public function test_payment_link_requires_a_quoted_price(): void
    {
        $a = $this->actingAdmin();
        $inq = $this->makeInquiry(); // no quoted_price

        $a->postJson("/admin/api/inquiries/{$inq->id}/payment-link")->assertStatus(422);
    }

    public function test_payment_endpoint_is_public_but_link_must_be_valid(): void
    {
        $this->getJson('/api/payment/nonexistent-token')->assertStatus(404);
    }
}
