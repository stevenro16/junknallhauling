<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Turns a notification event into actual messages: texts each admin who opted in
 * for that event (per their saved prefs + mobile), and texts the customer when a
 * customer-facing message exists AND the gate allows it
 * (Inquiry::customerNotificationChannel()). Failures are swallowed + logged so a
 * notification can never break the action that triggered it.
 *
 * Event keys match config('business.notification_events').
 */
class Notifier
{
    public function __construct(private SmsService $sms) {}

    public function fire(string $event, Inquiry $inquiry): void
    {
        try {
            $this->notifyAdmins($event, $inquiry);
            $this->notifyCustomer($event, $inquiry);
        } catch (\Throwable $e) {
            Log::warning('Notifier failed', ['event' => $event, 'inquiry' => $inquiry->id, 'error' => $e->getMessage()]);
        }
    }

    private function notifyAdmins(string $event, Inquiry $inquiry): void
    {
        $message = $this->adminMessage($event, $inquiry);
        if (! $message) {
            return;
        }

        foreach (Admin::where('active', true)->get() as $admin) {
            $prefs = $admin->notification_preferences ?? [];
            $channels = $prefs['events'][$event] ?? [];

            if (($channels['sms'] ?? false) && ($phone = $prefs['phone'] ?? null)) {
                $this->sms->send($phone, $message);
            }
            if (($channels['email'] ?? false) && ($email = $prefs['email'] ?? null)) {
                $this->email($email, $this->adminSubject($event), $message);
            }
        }
    }

    private function notifyCustomer(string $event, Inquiry $inquiry): void
    {
        $message = $this->customerMessage($event, $inquiry);
        if (! $message) {
            return;
        }

        // Send on the customer's preferred channel, only when it's enabled site-wide.
        $channel = $inquiry->customerNotificationChannel();
        if ($channel === 'sms') {
            $this->sms->send($inquiry->phone, $message);
        } elseif ($channel === 'email') {
            $this->email($inquiry->email, $this->customerSubject($event), $message);
        }
    }

    /** Send a plain-text email; never throws (logged on failure). */
    private function email(?string $to, string $subject, string $body): void
    {
        if (! $to) {
            return;
        }
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Notifier email failed', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }

    private function adminMessage(string $event, Inquiry $inquiry): ?string
    {
        $name = $inquiry->name ?: 'A customer';
        $ref = $inquiry->ref;

        return match ($event) {
            'new_quote' => "New quote request from {$name} ({$this->forLabel($inquiry)}). Ref {$ref}.",
            'details_submitted' => "{$name} submitted their quote details. Ref {$ref}.",
            'agreement_signed' => "{$name} signed the rental agreement. Ref {$ref}.",
            'payment_received' => "Payment received from {$name}{$this->amount($inquiry)}. Ref {$ref}.",
            default => null,
        };
    }

    private function customerMessage(string $event, Inquiry $inquiry): ?string
    {
        $biz = config('business.name');
        $first = trim(explode(' ', (string) $inquiry->name)[0] ?? '');
        $hi = $first !== '' ? "Hi {$first}, " : '';

        return match ($event) {
            'new_quote' => "{$hi}thanks for your request to {$biz} (ref {$inquiry->ref}). We'll be in touch shortly.",
            'payment_received' => "{$hi}we've received your payment{$this->amount($inquiry)}. Thank you! — {$biz}",
            default => null,
        };
    }

    private function adminSubject(string $event): string
    {
        $biz = config('business.name');

        return match ($event) {
            'new_quote' => "New quote request — {$biz}",
            'details_submitted' => "Customer submitted details — {$biz}",
            'agreement_signed' => "Rental agreement signed — {$biz}",
            'payment_received' => "Payment received — {$biz}",
            default => $biz,
        };
    }

    private function customerSubject(string $event): string
    {
        $biz = config('business.name');

        return match ($event) {
            'new_quote' => "We received your request — {$biz}",
            'payment_received' => "Payment received — {$biz}",
            default => $biz,
        };
    }

    private function amount(Inquiry $inquiry): string
    {
        $price = (float) $inquiry->quoted_price;

        return $price > 0 ? ' of $'.number_format($price, 2) : '';
    }

    private function forLabel(Inquiry $inquiry): string
    {
        return $inquiry->equipment_type ?: ($inquiry->service_type ?: 'service');
    }
}
