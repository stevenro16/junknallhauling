<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AppSetting;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Per-admin notification preferences (which events, which channels). Settings only —
// nothing fires a notification yet; this just persists each admin's choices.
class NotificationSettingsController extends Controller
{
    /**
     * PATCH /admin/api/notifications
     * Save the current admin's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $admin = Admin::find($request->session()->get('admin_id'));
        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $email = trim((string) $request->input('email'));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Please enter a valid notification email address.'], 422);
        }

        // Keep only digits for the SMS phone; store the cleaned value.
        $phone = preg_replace('/[^0-9]/', '', (string) $request->input('phone'));

        // Normalize the submitted toggles against the known event catalog so only
        // recognized events/channels are persisted.
        $submitted = (array) $request->input('events');
        $events = [];
        foreach (array_keys(config('business.notification_events', [])) as $key) {
            $row = (array) ($submitted[$key] ?? []);
            $events[$key] = [
                'email' => filter_var($row['email'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'sms' => filter_var($row['sms'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $prefs = [
            'email' => $email,
            'phone' => $phone,
            'events' => $events,
        ];

        $admin->update(['notification_preferences' => $prefs]);

        return response()->json(['success' => true, 'preferences' => $prefs]);
    }

    /**
     * PATCH /admin/api/notifications/customer
     * Global master switches for customer-facing notification channels. A customer
     * is only ever contacted on their preferred channel, and only if it's on here
     * (see Inquiry::customerNotificationChannel()).
     */
    public function updateCustomer(Request $request): JsonResponse
    {
        AppSetting::set('customer_notify_email', $request->boolean('email'));
        AppSetting::set('customer_notify_sms', $request->boolean('sms'));

        return response()->json([
            'success' => true,
            'email' => AppSetting::bool('customer_notify_email'),
            'sms' => AppSetting::bool('customer_notify_sms'),
        ]);
    }

    /**
     * POST /admin/api/notifications/test-sms
     * Send a one-off test text to confirm Twilio is set up. Uses the number posted
     * from the form (falls back to the admin's saved mobile).
     */
    public function testSms(Request $request, SmsService $sms): JsonResponse
    {
        if (! $sms->configured()) {
            return response()->json(['error' => 'Twilio isn’t configured on the server yet — add the TWILIO_* keys to .env.'], 422);
        }

        $admin = Admin::find($request->session()->get('admin_id'));
        $phone = trim((string) $request->input('phone'))
            ?: ($admin?->notification_preferences['phone'] ?? '');

        if ($phone === '') {
            return response()->json(['error' => 'Enter a mobile number first.'], 422);
        }

        $sent = $sms->send($phone, 'Test from '.config('business.name').' — your SMS notifications are working.');

        return $sent
            ? response()->json(['success' => true])
            : response()->json(['error' => 'Couldn’t send. Check the number and your Twilio account (logs have details).'], 502);
    }

    /**
     * POST /admin/api/notifications/test-email
     * Send a one-off test email to confirm mail delivery is set up. Uses the
     * address posted from the form (falls back to the admin's saved/account email).
     */
    public function testEmail(Request $request): JsonResponse
    {
        $admin = Admin::find($request->session()->get('admin_id'));
        $email = trim((string) $request->input('email'))
            ?: ($admin?->notification_preferences['email'] ?? $admin?->email ?? '');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Enter a valid email address first.'], 422);
        }

        // The default 'log' mailer writes to the log instead of delivering, so a
        // test there would never reach an inbox — say so plainly.
        if (config('mail.default') === 'log') {
            return response()->json(['error' => 'Email is in log-only mode (MAIL_MAILER=log) — set up a real mailer (SMTP/Resend/etc.) to deliver.'], 422);
        }

        try {
            Mail::send('emails.notification', [
                'heading' => 'Test notification',
                'lines' => ['This is a test from '.config('business.name').'. Your email notifications are set up and working.'],
            ], function ($message) use ($email) {
                $message->to($email)->subject(config('business.name').' — test notification');
            });
        } catch (\Throwable $e) {
            Log::warning('Test email failed', ['to' => $email, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Couldn’t send — check the mail settings (logs have details).'], 502);
        }

        return response()->json(['success' => true]);
    }
}
