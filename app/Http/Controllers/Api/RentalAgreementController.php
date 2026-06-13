<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalAgreement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalAgreementController extends Controller
{
    /**
     * GET /api/rental-agreement/{token}
     * Returns the linked quote data + agreement status for the public form.
     */
    public function show(string $token): JsonResponse
    {
        $agreement = RentalAgreement::where('token', $token)->first();

        if (! $agreement) {
            return response()->json(['error' => 'Invalid or expired link'], 404);
        }
        if ($agreement->signed_at) {
            return response()->json(['error' => 'This link has already been used', 'alreadySigned' => true], 410);
        }
        if ($agreement->cancelled_at) {
            return response()->json(['error' => 'This rental agreement link has been cancelled by the admin.', 'cancelled' => true], 410);
        }
        if ($agreement->expires_at && $agreement->expires_at < now()->toISOString()) {
            return response()->json(['error' => 'This link has expired'], 410);
        }

        $inquiry = $agreement->inquiry;
        if (! $inquiry) {
            return response()->json(['error' => 'Associated quote no longer exists'], 404);
        }

        return response()->json([
            'agreement' => [
                'token'      => $agreement->token,
                'expires_at' => $agreement->expires_at,
            ],
            'inquiry' => [
                'ref'                       => $inquiry->ref,
                'name'                      => $inquiry->name,
                'phone'                     => $inquiry->phone,
                'email'                     => $inquiry->email,
                'service_type'              => $inquiry->service_type,
                'equipment_type'            => $inquiry->equipment_type,
                'equipment_rental_duration' => $inquiry->equipment_rental_duration,
                'equipment_rental_unit'     => $inquiry->equipment_rental_unit,
                'quoted_price'              => $inquiry->quoted_price,
                'confirmed_date_time'       => $inquiry->confirmed_date_time,
                'address'                   => $inquiry->address,
                'admin_notes'               => $inquiry->admin_notes,
            ],
        ]);
    }

    /**
     * POST /api/rental-agreement/{token}
     * Customer submits the signed agreement (one-time use).
     */
    public function sign(string $token, Request $request): JsonResponse
    {
        $formData  = $request->input('form_data');
        $signature = $request->input('signature_base64');

        if (! $formData || ! $signature) {
            return response()->json(['error' => 'Missing form data or signature'], 400);
        }

        $agreement = RentalAgreement::where('token', $token)->first();
        if (! $agreement) {
            return response()->json(['error' => 'Invalid link'], 404);
        }
        if ($agreement->signed_at) {
            return response()->json(['error' => 'This agreement has already been signed'], 410);
        }
        if ($agreement->expires_at && $agreement->expires_at < now()->toISOString()) {
            return response()->json(['error' => 'This link has expired'], 410);
        }

        $ip = trim(explode(',', (string) $request->header('x-forwarded-for'))[0])
            ?: $request->header('x-real-ip')
            ?: $request->ip();

        $signedAt = now()->toISOString();

        // One-time use guard at the SQL level. A query-builder update() bypasses
        // the form_data array cast, so encode it explicitly.
        RentalAgreement::where('token', $token)->whereNull('signed_at')->update([
            'form_data'        => json_encode($formData),
            'signature_base64' => $signature,
            'signed_at'        => $signedAt,
            'ip_address'       => $ip ?: null,
        ]);

        return response()->json(['success' => true, 'signed_at' => $signedAt]);
    }
}
