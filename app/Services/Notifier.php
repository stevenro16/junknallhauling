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
                // Admin email: branded template with a button straight to the record.
                $this->email($email, $this->adminSubject($event), [
                    'heading' => $this->adminHeading($event),
                    'lines' => [$this->adminIntro($event)],
                    'details' => $this->recordDetails($inquiry),
                    'ctaLabel' => 'View in dashboard',
                    'ctaUrl' => route('admin.inquiries.show', $inquiry->id),
                ]);
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
            // Customer email: branded template; the record link is the public status lookup.
            $this->email($inquiry->email, $this->customerSubject($event), [
                'heading' => $this->customerHeading($event),
                'lines' => [$message],
                'details' => $this->customerDetails($event, $inquiry),
                'ctaLabel' => 'Check your request status',
                'ctaUrl' => route('status'),
                'footnote' => 'Reply STOP to opt out of texts at any time. Message and data rates may apply.',
            ]);
        }
    }

    /** Send a branded HTML email from the notification template; never throws. */
    private function email(?string $to, string $subject, array $data): void
    {
        if (! $to) {
            return;
        }
        try {
            Mail::send('emails.notification', $data, function ($message) use ($to, $subject) {
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

    private function adminHeading(string $event): string
    {
        return match ($event) {
            'new_quote' => 'New quote request',
            'details_submitted' => 'Customer submitted details',
            'agreement_signed' => 'Rental agreement signed',
            'payment_received' => 'Payment received',
            default => config('business.name'),
        };
    }

    private function adminIntro(string $event): string
    {
        return match ($event) {
            'new_quote' => 'A new quote request just came in. The details are below.',
            'details_submitted' => 'A customer completed their request details. The latest details are below.',
            'agreement_signed' => 'A customer signed their rental agreement.',
            'payment_received' => 'A payment was just recorded for this job.',
            default => '',
        };
    }

    private function customerHeading(string $event): string
    {
        return match ($event) {
            'new_quote' => 'We received your request',
            'payment_received' => 'Payment received',
            default => config('business.name'),
        };
    }

    /** Key facts about the job, shown as a detail table in admin emails. */
    private function recordDetails(Inquiry $inquiry): array
    {
        $rows = ['Customer' => $inquiry->name ?: '—'];
        if ($inquiry->phone) {
            $rows['Phone'] = $inquiry->phone;
        }
        if ($service = $inquiry->equipment_type ?: $inquiry->service_type) {
            $rows['Service'] = $service;
        }
        if ($inquiry->ref) {
            $rows['Reference'] = $inquiry->ref;
        }
        if ((float) $inquiry->quoted_price > 0) {
            $rows['Amount'] = '$'.number_format((float) $inquiry->quoted_price, 2);
        }

        return $rows;
    }

    private function customerDetails(string $event, Inquiry $inquiry): array
    {
        $rows = [];
        if ($inquiry->ref) {
            $rows['Reference'] = $inquiry->ref;
        }
        if ($event === 'payment_received' && (float) $inquiry->quoted_price > 0) {
            $rows['Amount paid'] = '$'.number_format((float) $inquiry->quoted_price, 2);
        }

        return $rows;
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
