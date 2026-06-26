<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\Inquiry;
use App\Services\Notifier;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function configureTwilio(): void
    {
        config([
            'services.twilio.sid' => 'AC_test',
            'services.twilio.token' => 'tok_test',
            'services.twilio.from' => '+15005550006',
        ]);
    }

    public function test_sms_is_noop_when_unconfigured(): void
    {
        Http::fake();
        config(['services.twilio.sid' => null, 'services.twilio.token' => null, 'services.twilio.from' => null]);

        $this->assertFalse((new SmsService)->send('9095550000', 'hi'));
        Http::assertNothingSent();
    }

    public function test_e164_normalization(): void
    {
        $sms = new SmsService;
        $this->assertSame('+19095550000', $sms->e164('(909) 555-0000'));
        $this->assertSame('+19095550000', $sms->e164('19095550000'));
        $this->assertSame('+447700900000', $sms->e164('+447700900000'));
        $this->assertNull($sms->e164(''));
    }

    public function test_new_quote_texts_optedin_admin_and_preferring_customer(): void
    {
        $this->configureTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);

        // Admin opted into SMS for new_quote, with a saved mobile.
        Admin::create([
            'username' => 'boss', 'role' => 'admin', 'active' => true,
            'password_hash' => Hash::make('x'), 'must_change_password' => false,
            'notification_preferences' => [
                'phone' => '9095551111',
                'events' => ['new_quote' => ['email' => false, 'sms' => true]],
            ],
        ]);

        AppSetting::set('customer_notify_sms', true);

        $inquiry = Inquiry::create([
            'name' => 'Pat Doe', 'phone' => '9095552222', 'email' => 'p@e.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'phone',
        ]);

        app(Notifier::class)->fire('new_quote', $inquiry);

        // One text to the admin, one to the customer.
        Http::assertSentCount(2);
        Http::assertSent(fn ($req) => $req['To'] === '+19095551111');   // admin
        Http::assertSent(fn ($req) => $req['To'] === '+19095552222');   // customer
    }

    public function test_customer_not_texted_when_global_sms_off(): void
    {
        $this->configureTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);

        AppSetting::set('customer_notify_sms', false);   // master switch off

        $inquiry = Inquiry::create([
            'name' => 'Pat', 'phone' => '9095552222', 'email' => 'p@e.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'phone',
        ]);

        app(Notifier::class)->fire('new_quote', $inquiry);

        // No opted-in admins and customer SMS off → nothing sent.
        Http::assertNothingSent();
    }

    public function test_test_sms_endpoint(): void
    {
        $this->configureTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);

        $admin = Admin::create([
            'username' => 'boss', 'role' => 'admin', 'active' => true,
            'password_hash' => Hash::make('x'), 'must_change_password' => false,
        ]);

        $this->withSession(['admin_id' => $admin->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false])
            ->postJson('/admin/api/notifications/test-sms', ['phone' => '(909) 555-3333'])
            ->assertOk()->assertJson(['success' => true]);

        Http::assertSent(fn ($req) => $req['To'] === '+19095553333');
    }
}
