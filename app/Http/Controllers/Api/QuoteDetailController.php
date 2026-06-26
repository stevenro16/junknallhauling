<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\QuoteDetailRequest;
use App\Models\RentalAgreement;
use App\Services\GeocodeService;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                'address_street' => $inquiry->address_street,
                'address_city' => $inquiry->address_city,
                'address_state' => $inquiry->address_state ?: 'CA',
                'zip_code' => $inquiry->zip_code,
                'preferred_contact_method' => $inquiry->preferred_contact_method,
                'preferred_day' => $inquiry->preferred_day,
                'preferred_time' => $inquiry->preferred_time,
                'service_type' => $inquiry->service_type,
                'equipment_type' => $inquiry->equipment_type,
                'equipment_rental_duration' => $inquiry->equipment_rental_duration,
                'equipment_rental_unit' => $inquiry->equipment_rental_unit,
                'confirmed_date_time' => $inquiry->confirmed_date_time,
                'quoted_price' => $inquiry->quoted_price,
            ],
            'is_equipment' => $this->isEquipment($inquiry),
            // Any item (service or equipment) with an attached agreement that isn't
            // signed yet must complete one.
            'needs_agreement' => $inquiry->needsAgreement(),
        ]);
    }

    private function isEquipment(Inquiry $inquiry): bool
    {
        return $inquiry->service_type === 'equipment' || ! empty($inquiry->equipment_type);
    }

    /**
     * POST /api/quote-details/{token}
     * Customer submits the completed details (one-time use). Updates the quote and
     * moves it to "finalize_scheduling" for the admin's final review. The phone
     * number is intentionally NOT updated.
     */
    public function submit(string $token, Request $request, Notifier $notifier): JsonResponse
    {
        $form = (array) $request->input('form_data');
        $signature = $request->input('signature_base64');
        $agreementSignature = $request->input('agreement_signature_base64');

        if (! $signature) {
            return response()->json(['error' => 'A signature is required.'], 422);
        }
        if (($form['confirm_datetime'] ?? false) !== true || ($form['confirm_amount'] ?? false) !== true) {
            return response()->json(['error' => 'Please confirm the scheduled date/time and the quoted amount.'], 422);
        }
        if (trim((string) ($form['name'] ?? '')) === ''
            || trim((string) ($form['address_street'] ?? '')) === ''
            || trim((string) ($form['address_city'] ?? '')) === '') {
            return response()->json(['error' => 'Name, street and city are required.'], 422);
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

        // When the item needs a rental agreement, the customer signs it on this same
        // form — so an email (to send the signed copy) and the 2nd signature + terms
        // acknowledgement are all required.
        $needsAgreement = $inquiry->needsAgreement();
        $email = trim((string) ($form['email'] ?? ''));

        if ($needsAgreement) {
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'A valid email is required to receive your signed rental agreement.'], 422);
            }
            if (! $agreementSignature) {
                return response()->json(['error' => 'Please sign the rental agreement.'], 422);
            }
            if (($form['agreed_to_terms'] ?? false) !== true) {
                return response()->json(['error' => 'Please confirm you agree to the rental agreement terms.'], 422);
            }
        } elseif (($form['preferred_contact_method'] ?? '') === 'email' && $email === '') {
            return response()->json(['error' => 'An email is required when email is the preferred contact method.'], 422);
        }

        // Update the quote from the customer's submission — never the phone number.
        $street = trim((string) ($form['address_street'] ?? ''));
        $city = trim((string) ($form['address_city'] ?? ''));
        $state = trim((string) ($form['address_state'] ?? '')) ?: 'CA';
        $zip = trim((string) ($form['zip_code'] ?? ''));

        $updates = [
            'name' => trim((string) $form['name']),
            'email' => trim((string) ($form['email'] ?? '')),
            'address_street' => $street,
            'address_city' => $city,
            'address_state' => $state,
            'zip_code' => $zip,
            'address' => Inquiry::composeAddress($street, $city, $state, $zip),
            'preferred_contact_method' => in_array(($form['preferred_contact_method'] ?? ''), ['phone', 'email'], true)
                ? $form['preferred_contact_method'] : $inquiry->preferred_contact_method,
            'preferred_day' => trim((string) ($form['preferred_day'] ?? '')) ?: null,
            'preferred_time' => trim((string) ($form['preferred_time'] ?? '')) ?: null,
            'status' => 'finalize_scheduling',
        ];

        // Up to 2 customer photos (image data URLs, ≤5MB each ≈ 7MB base64).
        $photos = array_values(array_filter((array) ($form['photos'] ?? []), function ($p) {
            return is_string($p) && preg_match('#^data:image/[a-z.+-]+;base64,#i', $p) && strlen($p) <= 7_500_000;
        }));
        if ($photos) {
            $updates['photos'] = array_slice($photos, 0, 2);
        }

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

        $notifier->fire('details_submitted', $inquiry);

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

        $response = ['success' => true, 'signed_at' => $signedAt];

        // Rental agreement is signed on this same form (2nd signature): mint the link,
        // sign it with a frozen snapshot of the terms, and email the customer a copy.
        if ($needsAgreement && ($agreement = $inquiry->ensureAgreementLink())) {
            $this->signAgreement($agreement, $inquiry, (string) $agreementSignature, $ip);
            $notifier->fire('agreement_signed', $inquiry);
            $response['agreement_signed'] = true;
        }

        return response()->json($response);
    }

    /** Sign the rental agreement (snapshot + 2nd signature) and email the customer the copy. */
    private function signAgreement(RentalAgreement $agreement, Inquiry $inquiry, string $signature, ?string $ip): void
    {
        $snapshot = $agreement->effectiveContent();
        $signedAt = now()->toISOString();

        // One-time-use guard at the SQL level (query-builder bypasses casts).
        RentalAgreement::where('token', $agreement->token)->whereNull('signed_at')->update([
            'form_data' => json_encode([
                'agreed_to_terms' => true,
                'signed_name' => $inquiry->name,
                'signed_via' => 'quote_details',
            ]),
            'content_snapshot' => json_encode($snapshot),
            'signature_base64' => $signature,
            'signed_at' => $signedAt,
            'ip_address' => $ip ?: null,
        ]);

        $this->emailSignedAgreement($inquiry, $snapshot, $signedAt);
    }

    /** Email the customer their signed agreement copy; never throws. */
    private function emailSignedAgreement(Inquiry $inquiry, array $snapshot, string $signedAt): void
    {
        if (! $inquiry->email) {
            return;
        }
        try {
            Mail::send('emails.agreement', [
                'inquiry' => $inquiry,
                'content' => $snapshot,
                'signedAt' => $signedAt,
            ], function ($message) use ($inquiry, $snapshot) {
                $message->to($inquiry->email)
                    ->subject(($snapshot['title'] ?? 'Rental Agreement').' — '.config('business.name'));
            });
        } catch (\Throwable $e) {
            Log::warning('Signed agreement email failed', ['inquiry' => $inquiry->id, 'error' => $e->getMessage()]);
        }
    }
}
