<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Inquiry;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Honeypot — if filled, silently succeed (bot).
        if (! empty($data['website'])) {
            return response()->json(['success' => true]);
        }

        $inquiry = Inquiry::create([
            'name'                      => $data['name'],
            'phone'                     => $data['phone'],
            'email'                     => $data['email'],
            'service_type'              => $data['service_type'],
            'description'               => $data['description'] ?? null,
            'photo_base64'              => $data['photo_base64'] ?? null,
            'photo_mime'                => $data['photo_mime'] ?? null,
            'zip_code'                  => $data['zip_code'],
            'preferred_day'             => $data['preferred_day'] ?? null,
            'preferred_time'            => $data['preferred_time'] ?? null,
            'equipment_type'            => $data['equipment_type'] ?? null,
            'preferred_contact_method'  => $data['preferred_contact_method'] ?? 'phone',
            'equipment_rental_duration' => $data['equipment_rental_duration'] ?? null,
            'equipment_rental_unit'     => $data['equipment_rental_unit'] ?? null,
            'initial_estimated_quote'   => $data['initial_estimated_quote'] ?? null,
        ]);

        // Auto-apply the service catalog's default duration to new inquiries.
        $svc = ServiceCatalog::where('key', $data['service_type'])->first();
        if ($svc && $svc->default_duration_minutes) {
            $inquiry->update(['expected_duration_minutes' => $svc->default_duration_minutes]);
        }

        return response()->json([
            'success' => true,
            'ref'     => $inquiry->ref,
            'id'      => $inquiry->id,
        ]);
    }
}
