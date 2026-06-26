<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Inquiry;
use App\Models\ServiceCatalog;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function store(StoreQuoteRequest $request, Notifier $notifier): JsonResponse
    {
        $data = $request->validated();

        // Honeypot — if filled, silently succeed (bot).
        if (! empty($data['website'])) {
            return response()->json(['success' => true]);
        }

        // "Help Me Decide" project photos — keep only valid image data URLs (≤ ~7MB
        // base64 each), capped at 3. Stored the same way as request-details photos.
        $photos = array_values(array_filter((array) ($data['photos'] ?? []), function ($p) {
            return is_string($p) && preg_match('#^data:image/[a-z.+-]+;base64,#i', $p) && strlen($p) <= 7_500_000;
        }));

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'service_type' => $data['service_type'],
            'description' => $data['description'] ?? null,
            'photo_base64' => $data['photo_base64'] ?? null,
            'photo_mime' => $data['photo_mime'] ?? null,
            'photos' => $photos ? array_slice($photos, 0, 3) : null,
            'zip_code' => $data['zip_code'],
            'preferred_day' => $data['preferred_day'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null,
            'equipment_type' => $data['equipment_type'] ?? null,
            'preferred_contact_method' => $data['preferred_contact_method'] ?? 'phone',
            'urgency' => $data['urgency'] ?? 'routine',
            'equipment_rental_duration' => $data['equipment_rental_duration'] ?? null,
            'equipment_rental_unit' => $data['equipment_rental_unit'] ?? null,
            'initial_estimated_quote' => $data['initial_estimated_quote'] ?? null,
        ]);

        // Auto-apply the service catalog's default duration to new inquiries.
        $svc = ServiceCatalog::where('key', $data['service_type'])->first();
        if ($svc && $svc->default_duration_minutes) {
            $inquiry->update(['expected_duration_minutes' => $svc->default_duration_minutes]);
        }

        $notifier->fire('new_quote', $inquiry);

        return response()->json([
            'success' => true,
            'ref' => $inquiry->ref,
            'id' => $inquiry->id,
        ]);
    }
}
