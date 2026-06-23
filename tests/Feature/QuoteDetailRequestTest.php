<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuoteDetailRequestTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): static
    {
        $a = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $a->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    private function inquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Jane', 'phone' => '9095550000', 'email' => 'jane@example.com',
            'service_type' => 'junk-removal', 'status' => 'quoted',
            'confirmed_date_time' => '2026-06-25T10:00', 'quoted_price' => 250,
        ], $overrides));
    }

    public function test_create_link_requires_datetime_and_price(): void
    {
        $session = $this->adminSession();
        $inq = $this->inquiry(['confirmed_date_time' => null, 'quoted_price' => null]);

        $session->postJson("/admin/api/inquiries/{$inq->id}/detail-request")->assertStatus(422);
    }

    public function test_create_link_succeeds_with_datetime_and_price(): void
    {
        $session = $this->adminSession();
        $inq = $this->inquiry();

        $res = $session->postJson("/admin/api/inquiries/{$inq->id}/detail-request")->assertOk();
        $this->assertNotEmpty($res->json('detail_request.token'));
        $this->assertStringContainsString('/quote-details/', $res->json('detail_request.url'));
    }

    public function test_public_show_returns_prefill_and_guards_invalid(): void
    {
        $inq = $this->inquiry();
        $token = $this->makeToken($inq);

        $this->getJson("/api/quote-details/{$token}")
            ->assertOk()
            ->assertJsonPath('inquiry.ref', $inq->fresh()->ref)
            ->assertJsonPath('inquiry.phone', '9095550000');

        $this->getJson('/api/quote-details/nope')->assertStatus(404);
    }

    public function test_submit_updates_quote_sets_status_and_keeps_phone(): void
    {
        $inq = $this->inquiry();
        $token = $this->makeToken($inq);

        $this->postJson("/api/quote-details/{$token}", [
            'form_data' => [
                'name' => 'Jane Customer', 'email' => 'new@example.com',
                'address' => '123 Main St, Yucaipa, CA', 'zip_code' => '92399',
                'preferred_day' => 'Monday', 'preferred_time' => 'Morning (8am - 12pm)',
                'preferred_contact_method' => 'email',
                'confirm_datetime' => true, 'confirm_amount' => true,
            ],
            'signature_base64' => 'data:image/png;base64,iVBORw0KGgo=',
        ])->assertOk()->assertJsonPath('success', true);

        $fresh = $inq->fresh();
        $this->assertSame('finalize_scheduling', $fresh->status);
        $this->assertSame('Jane Customer', $fresh->name);
        $this->assertSame('new@example.com', $fresh->email);
        $this->assertSame('123 Main St, Yucaipa, CA', $fresh->address);
        $this->assertSame('9095550000', $fresh->phone);   // phone never changes
        $this->assertSame('Monday', $fresh->preferred_day);
    }

    public function test_submit_requires_signature_and_both_confirmations(): void
    {
        $inq = $this->inquiry();
        $token = $this->makeToken($inq);

        // Missing signature.
        $this->postJson("/api/quote-details/{$token}", [
            'form_data' => ['name' => 'X', 'address' => 'Y', 'confirm_datetime' => true, 'confirm_amount' => true],
        ])->assertStatus(422);

        // Unconfirmed boxes.
        $this->postJson("/api/quote-details/{$token}", [
            'form_data' => ['name' => 'X', 'address' => 'Y', 'confirm_datetime' => true, 'confirm_amount' => false],
            'signature_base64' => 'data:image/png;base64,iVBORw0KGgo=',
        ])->assertStatus(422);
    }

    public function test_submit_is_one_time_use(): void
    {
        $inq = $this->inquiry();
        $token = $this->makeToken($inq);

        $payload = [
            'form_data' => ['name' => 'Jane', 'address' => '123 Main St', 'confirm_datetime' => true, 'confirm_amount' => true],
            'signature_base64' => 'data:image/png;base64,iVBORw0KGgo=',
        ];

        $this->postJson("/api/quote-details/{$token}", $payload)->assertOk();
        $this->postJson("/api/quote-details/{$token}", $payload)->assertStatus(410);
    }

    /** Mint a usable link for an inquiry via the admin endpoint and return its token. */
    private function makeToken(Inquiry $inq): string
    {
        return $this->adminSession()
            ->postJson("/admin/api/inquiries/{$inq->id}/detail-request")
            ->json('detail_request.token');
    }
}
