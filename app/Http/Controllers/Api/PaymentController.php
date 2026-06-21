<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * GET /api/payment/{token}
     * Returns the quote amount + summary for the public payment page.
     */
    public function show(string $token): JsonResponse
    {
        $link = PaymentLink::where('token', $token)->first();

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
        ]);
    }

    /**
     * POST /api/payment/{token}
     * Records the customer's payment (one-time use). Placeholder for a real
     * gateway — when one is added, charge first, then run this on success.
     */
    public function pay(string $token, Request $request): JsonResponse
    {
        $link = PaymentLink::where('token', $token)->first();
        if (! $link) {
            return response()->json(['error' => 'Invalid link'], 404);
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

        $ip = trim(explode(',', (string) $request->header('x-forwarded-for'))[0])
            ?: $request->header('x-real-ip')
            ?: $request->ip();

        $paidAt = now()->toISOString();
        $method = 'Online Payment';

        // One-time-use guard at the SQL level.
        PaymentLink::where('token', $token)->whereNull('paid_at')->update([
            'paid_at' => $paidAt,
            'payment_method' => $method,
            'ip_address' => $ip ?: null,
        ]);

        // Reflect the payment on the inquiry so the admin sees it as received.
        $inquiry = $link->inquiry;
        if ($inquiry) {
            $inquiry->update([
                'payment_method' => $method,
                'payment_date' => now()->format('Y-m-d\TH:i'),
            ]);
            $inquiry->logAudit('payment_received');
        }

        return response()->json(['success' => true, 'paid_at' => $paidAt]);
    }
}
