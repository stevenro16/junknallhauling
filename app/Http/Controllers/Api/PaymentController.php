<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Stripe\Webhook;

class PaymentController extends Controller
{
    /**
     * GET /api/payment/{token}
     * Returns the quote amount + summary for the public payment page.
     */
    public function show(string $token): JsonResponse
    {
        $link = PaymentLink::where('token', $token)->first();

        if ($error = $this->linkError($link)) {
            return $error;
        }

        $inquiry = $link->inquiry;
        if (! $inquiry) {
            return response()->json(['error' => 'Associated quote no longer exists'], 404);
        }

        return response()->json([
            'payment' => [
                'token' => $link->token,
                'amount' => $link->amount,
            ],
            'inquiry' => [
                'ref' => $inquiry->ref,
                'name' => $inquiry->name,
                'service_type' => $inquiry->service_type,
                'equipment_type' => $inquiry->equipment_type,
                'equipment_rental_duration' => $inquiry->equipment_rental_duration,
                'equipment_rental_unit' => $inquiry->equipment_rental_unit,
                'confirmed_date_time' => $inquiry->confirmed_date_time,
                'address' => $inquiry->address,
            ],
            'business' => [
                'name' => config('business.name'),
                'phone' => config('business.phone'),
            ],
            'stripe' => $this->stripeEnabled(),
        ]);
    }

    /**
     * POST /api/payment/{token}
     * Begins payment. With Stripe configured, creates a Checkout Session and returns
     * its hosted URL; otherwise records the (non-charging) placeholder payment.
     */
    public function pay(string $token, Request $request): JsonResponse
    {
        $link = PaymentLink::where('token', $token)->first();

        if ($error = $this->linkError($link)) {
            return $error;
        }

        if (! $this->stripeEnabled()) {
            $this->markPaid($link, 'Online Payment', $this->clientIp($request));

            return response()->json(['success' => true, 'paid_at' => now()->toISOString()]);
        }

        $inquiry = $link->inquiry;
        $base = route('payment.show', $token);

        try {
            $session = $this->stripe()->checkout->sessions->create([
                'mode' => 'payment',
                'success_url' => $base.'?status=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $base.'?status=cancel',
                'client_reference_id' => $link->token,
                'metadata' => ['payment_link_token' => $link->token, 'inquiry_id' => $link->inquiry_id],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int) round(((float) $link->amount) * 100),
                        'product_data' => [
                            'name' => config('business.name').($inquiry->ref ? ' — Quote '.$inquiry->ref : ''),
                        ],
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not start the payment. Please try again.'], 502);
        }

        $link->update(['stripe_session_id' => $session->id]);

        return response()->json(['checkout_url' => $session->url]);
    }

    /**
     * POST /api/payment/{token}/confirm
     * Called when the customer returns from Stripe — verifies the session is paid and
     * records it (idempotent). The webhook is the authoritative backstop.
     */
    public function confirm(string $token, Request $request): JsonResponse
    {
        $link = PaymentLink::where('token', $token)->first();
        if (! $link) {
            return response()->json(['error' => 'Invalid link'], 404);
        }
        if ($link->paid_at) {
            return response()->json(['paid' => true]);
        }
        if (! $this->stripeEnabled()) {
            return response()->json(['paid' => false]);
        }

        $sessionId = (string) $request->input('session_id');
        if ($sessionId === '' || ($link->stripe_session_id && $sessionId !== $link->stripe_session_id)) {
            return response()->json(['paid' => false]);
        }

        try {
            $session = $this->stripe()->checkout->sessions->retrieve($sessionId);
        } catch (\Throwable $e) {
            return response()->json(['paid' => false]);
        }

        if (($session->payment_status ?? '') === 'paid') {
            $this->markPaid($link, 'Card (Stripe)', $this->clientIp($request));

            return response()->json(['paid' => true]);
        }

        return response()->json(['paid' => false]);
    }

    /**
     * POST /api/stripe/webhook
     * Authoritative payment confirmation from Stripe. Signature-verified.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();

        try {
            $event = $secret
                ? Webhook::constructEvent($payload, (string) $request->header('Stripe-Signature'), $secret)
                : json_decode($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $type = is_object($event) ? ($event->type ?? '') : '';
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event->data->object;
            if (($session->payment_status ?? '') === 'paid') {
                $token = $session->metadata->payment_link_token ?? $session->client_reference_id ?? null;
                $link = $token ? PaymentLink::where('token', $token)->first() : null;
                if ($link && ! $link->paid_at) {
                    $this->markPaid($link, 'Card (Stripe)', null);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    /** Shared validity gate — returns a JSON error response, or null when the link is payable. */
    private function linkError(?PaymentLink $link): ?JsonResponse
    {
        if (! $link) {
            return response()->json(['error' => 'Invalid or expired link'], 404);
        }
        if ($link->paid_at) {
            return response()->json(['error' => 'This payment has already been completed.', 'alreadyPaid' => true], 410);
        }
        if ($link->cancelled_at) {
            return response()->json(['error' => 'This payment link has been cancelled.', 'cancelled' => true], 410);
        }
        if ($link->expires_at && $link->expires_at < now()->toISOString()) {
            return response()->json(['error' => 'This link has expired'], 410);
        }

        return null;
    }

    private function stripeEnabled(): bool
    {
        return ! empty(config('services.stripe.secret'));
    }

    private function stripe(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    private function clientIp(Request $request): ?string
    {
        $ip = trim(explode(',', (string) $request->header('x-forwarded-for'))[0])
            ?: $request->header('x-real-ip')
            ?: $request->ip();

        return $ip ?: null;
    }

    /** Mark a link (and its inquiry) paid — one-time, idempotent across confirm + webhook. */
    private function markPaid(PaymentLink $link, string $method, ?string $ip): void
    {
        // SQL-level one-time guard: only the first caller flips paid_at.
        $updated = PaymentLink::where('token', $link->token)->whereNull('paid_at')->update([
            'paid_at' => now()->toISOString(),
            'payment_method' => $method,
            'ip_address' => $ip,
        ]);
        if (! $updated) {
            return;
        }

        $inquiry = $link->inquiry;
        if ($inquiry) {
            $inquiry->update([
                'payment_method' => $method,
                'payment_date' => now()->format('Y-m-d\TH:i'),
            ]);
            $inquiry->logAudit('payment_received');
        }
    }
}
