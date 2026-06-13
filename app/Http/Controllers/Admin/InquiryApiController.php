<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Services\DemoSeeder;
use App\Services\GeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryApiController extends Controller
{
    public function __construct(private GeocodeService $geocoder) {}

    /** GET /admin/api/inquiries — list all (auto-seeds demo data on first use). */
    public function index(): JsonResponse
    {
        DemoSeeder::ensure();

        return response()->json(['inquiries' => Inquiry::orderByDesc('created_at')->get()]);
    }

    /** POST /admin/api/inquiries — quick-create from the New Quote modal. */
    public function store(Request $request): JsonResponse
    {
        $phone = trim((string) $request->input('phone'));
        if ($phone === '') {
            return response()->json(['error' => 'Phone number is required.'], 400);
        }

        $inquiry = Inquiry::create([
            'name'         => trim((string) $request->input('name')) ?: '',
            'phone'        => $phone,
            'email'        => trim((string) $request->input('email')) ?: '',
            'service_type' => 'other',
            'zip_code'     => trim((string) $request->input('zip_code')) ?: '',
        ]);

        return response()->json(['inquiry' => $inquiry], 201);
    }

    /** GET /admin/api/inquiries/{id} */
    public function show(string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['inquiry' => $inquiry]);
    }

    /** PATCH /admin/api/inquiries/{id} — partial update + geocoding + status logging. */
    public function update(string $id, Request $request): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $b = $request->all();
        $oldStatus = $inquiry->status;
        $updates = [];

        $strings = [
            'status', 'admin_notes', 'address', 'confirmed_date_time', 'equipment_type',
            'equipment_rental_unit', 'phone', 'email', 'preferred_contact_method',
            'payment_method', 'payment_date', 'payment_notes', 'service_type',
            'zip_code', 'preferred_day', 'preferred_time',
        ];
        foreach ($strings as $k) {
            if (array_key_exists($k, $b)) {
                $updates[$k] = $b[$k];
            }
        }

        foreach (['initial_estimated_quote', 'quoted_price'] as $k) {
            if (array_key_exists($k, $b)) {
                $updates[$k] = $b[$k] === null || $b[$k] === '' ? null : (float) $b[$k];
            }
        }
        foreach (['equipment_rental_duration', 'expected_duration_minutes'] as $k) {
            if (array_key_exists($k, $b)) {
                $updates[$k] = $b[$k] === null || $b[$k] === '' ? null : (int) $b[$k];
            }
        }
        foreach (['quote_verified', 'address_verified', 'date_time_verified', 'contact_verified'] as $k) {
            if (array_key_exists($k, $b)) {
                $updates[$k] = (bool) $b[$k];
            }
        }

        // Geocode whenever the address is being set/changed; clear coords when emptied.
        if (array_key_exists('address', $b)) {
            if (! empty($b['address'])) {
                $coords = $this->geocoder->geocode($b['address']);
                if ($coords) {
                    $updates['latitude'] = $coords['lat'];
                    $updates['longitude'] = $coords['lng'];
                }
            } else {
                $updates['latitude'] = null;
                $updates['longitude'] = null;
            }
        }

        $inquiry->update($updates);

        if (array_key_exists('status', $b) && $oldStatus !== $b['status']) {
            $inquiry->logStatusChange($oldStatus, $b['status']);
        }

        return response()->json(['inquiry' => $inquiry->fresh()]);
    }

    /** GET /admin/api/inquiries/counts — workqueue buckets for the sidebar badge + cards. */
    public function counts(): JsonResponse
    {
        $map = Inquiry::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $new = ($map['new'] ?? 0) + ($map['reviewing'] ?? 0) + ($map['quoted'] ?? 0);
        $scheduled = $map['scheduled'] ?? 0;
        $pending = $map['service_performed'] ?? 0;

        return response()->json([
            'new'            => (int) $new,
            'scheduled'      => (int) $scheduled,
            'pendingPayment' => (int) $pending,
            'workqueueTotal' => (int) ($new + $scheduled + $pending),
        ]);
    }

    /** GET /admin/api/inquiries/{id}/history */
    public function history(string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'history' => $inquiry->statusHistory()->orderByDesc('changed_at')->get(),
        ]);
    }

    /** POST /admin/api/inquiries/{id}/audit — log a custom audit event. */
    public function audit(string $id, Request $request): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $action = trim((string) $request->input('action'));
        if ($action !== '') {
            $inquiry->logAudit($action);
        }

        return response()->json(['success' => true]);
    }
}
