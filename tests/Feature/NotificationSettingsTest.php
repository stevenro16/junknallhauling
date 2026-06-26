<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(string $email = ''): array
    {
        $admin = Admin::create([
            'username' => 'boss',
            'email' => $email,
            'password_hash' => Hash::make('secret123'),
            'must_change_password' => false,
        ]);

        return [$admin, $this->withSession([
            'admin_id' => $admin->id, 'admin_username' => 'boss',
            'admin_role' => 'admin', 'admin_must_change' => false,
        ])];
    }

    public function test_notifications_section_renders(): void
    {
        [, $a] = $this->actingAdmin('boss@example.com');

        $a->get('/admin?section=notifications')
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('New quote request');
    }

    public function test_saves_and_normalizes_preferences(): void
    {
        [$admin, $a] = $this->actingAdmin();

        $a->patchJson('/admin/api/notifications', [
            'email' => 'me@example.com',
            'phone' => '(909) 459-9503',
            'events' => [
                'new_quote' => ['email' => true, 'sms' => true],
                'payment_received' => ['email' => false, 'sms' => true],
                'bogus_event' => ['email' => true, 'sms' => true],   // unknown — dropped
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $prefs = $admin->fresh()->notification_preferences;

        $this->assertSame('me@example.com', $prefs['email']);
        $this->assertSame('9094599503', $prefs['phone']);                 // digits only
        $this->assertSame(['email' => true, 'sms' => true], $prefs['events']['new_quote']);
        $this->assertSame(['email' => false, 'sms' => true], $prefs['events']['payment_received']);
        $this->assertArrayNotHasKey('bogus_event', $prefs['events']);     // unknown event ignored
        // Known event not submitted defaults to off.
        $this->assertSame(['email' => false, 'sms' => false], $prefs['events']['agreement_signed']);
    }

    public function test_rejects_invalid_email(): void
    {
        [, $a] = $this->actingAdmin();

        $a->patchJson('/admin/api/notifications', ['email' => 'not-an-email'])
            ->assertStatus(422);
    }

    public function test_saves_global_customer_toggles(): void
    {
        [, $a] = $this->actingAdmin();

        $a->patchJson('/admin/api/notifications/customer', ['email' => true, 'sms' => false])
            ->assertOk()->assertJson(['success' => true, 'email' => true, 'sms' => false]);

        $this->assertTrue(AppSetting::bool('customer_notify_email'));
        $this->assertFalse(AppSetting::bool('customer_notify_sms'));
    }

    /** The gate: contact only on the customer's preferred channel, and only if it's on. */
    public function test_customer_notification_channel_gate(): void
    {
        $emailCust = Inquiry::create([
            'name' => 'E', 'phone' => '9095550000', 'email' => 'e@e.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'email',
        ]);
        $phoneCust = Inquiry::create([
            'name' => 'P', 'phone' => '9095551111', 'email' => 'p@e.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'phone',
        ]);

        // Both channels off → nobody gets contacted.
        AppSetting::set('customer_notify_email', false);
        AppSetting::set('customer_notify_sms', false);
        $this->assertNull($emailCust->customerNotificationChannel());
        $this->assertNull($phoneCust->customerNotificationChannel());

        // Email on, SMS off → only the email-preferring customer, on email.
        AppSetting::set('customer_notify_email', true);
        $this->assertSame('email', $emailCust->customerNotificationChannel());
        $this->assertNull($phoneCust->customerNotificationChannel());   // prefers phone, SMS off → nothing

        // SMS on too → the phone-preferring customer now gets a text.
        AppSetting::set('customer_notify_sms', true);
        $this->assertSame('sms', $phoneCust->customerNotificationChannel());

        // Channel on but no address for it → still nothing (never falls back).
        $noEmail = Inquiry::create([
            'name' => 'N', 'phone' => '9095552222', 'email' => '',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'email',
        ]);
        $this->assertNull($noEmail->customerNotificationChannel());
    }

    public function test_test_email_rejects_log_only_mode(): void
    {
        [, $a] = $this->actingAdmin('boss@example.com');
        config(['mail.default' => 'log']);

        $a->postJson('/admin/api/notifications/test-email', ['email' => 'x@example.com'])
            ->assertStatus(422);
    }

    public function test_test_email_sends_with_a_real_mailer(): void
    {
        [, $a] = $this->actingAdmin('boss@example.com');
        config(['mail.default' => 'array']);

        $a->postJson('/admin/api/notifications/test-email', ['email' => 'x@example.com'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertCount(1, Mail::mailer('array')->getSymfonyTransport()->messages());
    }
}
