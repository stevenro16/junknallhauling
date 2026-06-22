<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\PaymentLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function linkFor(float $amount = 200): PaymentLink
    {
        $inq = Inquiry::create([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com',
            'service_type' => 'junk-removal', 'zip_code' => '92399', 'status' => 'service_performed',
            'quoted_price' => $amount,
        ]);

        return $inq->paymentLinks()->create(['token' => 'tok-'.$inq->id, 'amount' => $amount]);
    }

    public function test_placeholder_pay_marks_paid_when_stripe_is_unconfigured(): void
    {
        config(['services.stripe.secret' => null]);
        $link = $this->linkFor(150);

        $this->postJson("/api/payment/{$link->token}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($link->fresh()->paid_at);
        $this->assertSame('Online Payment', $link->inquiry->fresh()->payment_method);
    }

    public function test_show_reports_whether_stripe_is_enabled(): void
    {
        config(['services.stripe.secret' => null]);
        $link = $this->linkFor();

        $this->getJson("/api/payment/{$link->token}")
            ->assertOk()
            ->assertJsonPath('stripe', false)
            ->assertJsonPath('payment.amount', 200);
    }

    public function test_webhook_marks_link_paid_on_completed_session(): void
    {
        config(['services.stripe.webhook_secret' => null]); // accept unverified in tests
        $link = $this->linkFor(325);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'payment_status' => 'paid',
                'client_reference_id' => $link->token,
                'metadata' => ['payment_link_token' => $link->token],
            ]],
        ];

        $this->postJson('/api/stripe/webhook', $payload)->assertOk()->assertJsonPath('received', true);

        $this->assertNotNull($link->fresh()->paid_at);
        $this->assertSame('Card (Stripe)', $link->inquiry->fresh()->payment_method);
    }

    public function test_mark_paid_is_idempotent(): void
    {
        config(['services.stripe.secret' => null, 'services.stripe.webhook_secret' => null]);
        $link = $this->linkFor(100);

        $this->postJson("/api/payment/{$link->token}")->assertOk();
        $firstPaidAt = $link->fresh()->paid_at;

        // A late webhook for the same link must not overwrite the recorded payment.
        $this->postJson('/api/stripe/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['payment_status' => 'paid', 'client_reference_id' => $link->token, 'metadata' => ['payment_link_token' => $link->token]]],
        ])->assertOk();

        $this->assertSame($firstPaidAt, $link->fresh()->paid_at);
    }

    public function test_confirm_short_circuits_for_an_already_paid_link(): void
    {
        $link = $this->linkFor();
        $link->update(['paid_at' => now()->toISOString()]);

        $this->postJson("/api/payment/{$link->token}/confirm", ['session_id' => 'cs_test_x'])
            ->assertOk()
            ->assertJsonPath('paid', true);
    }
}
