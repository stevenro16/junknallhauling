<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\Inquiry;
use App\Services\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);   // capture mail in memory, no real send
    }

    private function sentMessages()
    {
        return Mail::mailer('array')->getSymfonyTransport()->messages();
    }

    public function test_admin_emailed_when_opted_into_email_for_event(): void
    {
        Admin::create([
            'username' => 'boss', 'role' => 'admin', 'active' => true,
            'password_hash' => Hash::make('x'), 'must_change_password' => false,
            'notification_preferences' => [
                'email' => 'boss@example.com',
                'events' => ['new_quote' => ['email' => true, 'sms' => false]],
            ],
        ]);

        $inquiry = Inquiry::create([
            'name' => 'Pat', 'phone' => '9095552222', 'email' => 'p@e.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'phone',
        ]);

        app(Notifier::class)->fire('new_quote', $inquiry);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('boss@example.com', $messages[0]->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_customer_emailed_only_when_prefers_email_and_globally_on(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Erin', 'phone' => '9095552222', 'email' => 'erin@example.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'email',
        ]);

        // Global email channel off → nothing.
        AppSetting::set('customer_notify_email', false);
        app(Notifier::class)->fire('new_quote', $inquiry);
        $this->assertCount(0, $this->sentMessages());

        // Turn it on → the email-preferring customer is emailed.
        AppSetting::set('customer_notify_email', true);
        app(Notifier::class)->fire('new_quote', $inquiry);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('erin@example.com', $messages[0]->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_phone_preferring_customer_is_not_emailed(): void
    {
        AppSetting::set('customer_notify_email', true);

        $inquiry = Inquiry::create([
            'name' => 'Sam', 'phone' => '9095552222', 'email' => 'sam@example.com',
            'service_type' => 'junk-removal', 'preferred_contact_method' => 'phone',
        ]);

        app(Notifier::class)->fire('new_quote', $inquiry);

        // Prefers phone → no email (and Twilio unconfigured → SMS is a no-op).
        $this->assertCount(0, $this->sentMessages());
    }
}
