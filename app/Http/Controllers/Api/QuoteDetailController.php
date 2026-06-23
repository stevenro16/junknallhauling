<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuoteDetailRequest;
use App\Services\GeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteDetailController extends Controller
{
    public function __construct(private GeocodeService $geocoder) {}

    /**
     * GET /api/quote-details/{token}
     * Returns the linked quote data for the public "request details" form.
     */
    public function show(string $token): JsonResponse
    {
        $req = QuoteDetailRequest::where('token', $token)->first();

        if (! $req) {
            return response()->json(['error' => 'Invalid or expired link'], 404);
        }
        if ($req->signed_at) {
            return response()->json(['error' => 'This link has already been used', 'alreadySigned' => true], 410);
        }
        if ($req->cancelled_at) {
            return response()->json(['error' => 'This link has been cancelled.', 'cancelled' => true], 410);
        }
        if ($req->expires_at && $req->expires_at < now()->toISOString()) {
            return response()->json(['error' => 'This link has expired'], 410);
        }

        $inquiry = $req->inquiry;
        if (! $inquiry) {
            return response()->json(['error' => 'Associated quote no longer exists'], 404);
        }

        return response()->json([
            'inquiry' => [
                'ref' => $inquiry->ref,
                'name' => $inquiry->name,
                'phone' => $inquiry->phone,           // shown read-only — cannot be changed
                'email' => $inquiry->email,
                'address' => $inquiry->address,
                'zip_code' => $inquiry->zip_code,
                'preferred_contact_method' => $inquiry->preferred_contact_method,
                'preferred_day' => $inquiry->preferred_day,
                'preferred_time' => $inquiry->preferred_time,
                'service_type' => $inquiry->service_type,
                'equipment_type' => $inquiry->equipment_type,
                'confirmed_date_time' => $inquiry->confirmed_date_time,
                'quoted_price' => $inquiry->quoted_price,
            ],
        ]);
    }

    /**
     * POST /api/quote-details/{token}
     * Customer submits the completed details (one-time use). Updates the quote and
     * moves it to "finalize_scheduling" for the admin's final review. The phone
     * number is intentionally NOT updated.
     */
    public function submit(string $token, Request $request): JsonResponse
    {
        $form = (array) $request->input('form_data');
        $signature = $request->input('signature_base64');

        if (! $signature) {
            return response()->json(['error' => 'A signature is required.'], 422);
        }
        if (($form['confirm_datetime'] ?? false) !== true || ($form['confirm_amount'] ?? false) !== true) {
            return response()->json(['error' => 'Please confirm the scheduled date/time and the quoted amount.'], 422);
        }
        if (trim((string) ($form['name'] ?? '')) === '' || trim((string) ($form['address'] ?? '')) === '') {
            return response()->json(['error' => 'Name and address are required.'], 422);
        }

        $req = QuoteDetailRequest::where('token', $token)->first();
        if (! $req) {
            return response()->json(['error' => 'Invalid link'], 404);
        }
        if ($req->signed_at) {
            return response()->json(['error' => 'This link has already been used'], 410);
        }
        if ($req->expires_at && $req->expires_at < now()->toISOString()) {
            return response()->json(['error' => 'This link has expired'], 410);
        }

        $inquiry = $req->inquiry;
        if (! $inquiry) {
            return response()->json(['error' => 'Associated quote no longer exists'], 404);
        }

        // Update the quote from the customer's submission — never the phone number.
        $updates = [
            'name' => trim((string) $form['name']),
            'email' => trim((string) ($form['email'] ?? '')),
            'address' => trim((string) $form['address']),
            'zip_code' => trim((string) ($form['zip_code'] ?? '')),
            'preferred_contact_method' => in_array(($form['preferred_contact_method'] ?? ''), ['phone', 'email'], true)
                ? $form['preferred_contact_method'] : $inquiry->preferred_contact_method,
            'preferred_day' => trim((string) ($form['preferred_day'] ?? '')) ?: null,
            'preferred_time' => trim((string) ($form['preferred_time'] ?? '')) ?: null,
            'status' => 'finalize_scheduling',
        ];

        $coords = $this->geocoder->geocode($updates['address']);
        if ($coords) {
            $updates['latitude'] = $coords['lat'];
            $updates['longitude'] = $coords['lng'];
        }

        $oldStatus = $inquiry->status;
        $inquiry->update($updates);
        if ($oldStatus !== 'finalize_scheduling') {
            $inquiry->logStatusChange($oldStatus, 'finalize_scheduling');
        }
        $inquiry->logAudit('customer_submitted_details');

        $ip = trim(explode(',', (string) $request->header('x-forwarded-for'))[0])
            ?: $request->header('x-real-ip')
            ?: $request->ip();

        $signedAt = now()->toISOString();

        // One-time use guard at the SQL level (query-builder update bypasses the cast).
        QuoteDetailRequest::where('token', $token)->whereNull('signed_at')->update([
            'form_data' => json_encode($form),
            'signature_base64' => $signature,
            'signed_at' => $signedAt,
            'ip_address' => $ip ?: null,
        ]);

        return response()->json(['success' => true, 'signed_at' => $signedAt]);
    }
}
