<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\PaymentLink;
use App\Models\QuoteDetailRequest;
use App\Models\RentalAgreement;
use App\Services\DemoSeeder;
use App\Services\GeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'name' => trim((string) $request->input('name')) ?: '',
            'phone' => $phone,
            'email' => trim((string) $request->input('email')) ?: '',
            'service_type' => 'other',
            'zip_code' => trim((string) $request->input('zip_code')) ?: '',
            'status' => 'quoted',   // admin-created quotes start as Quoted
        ]);

        return response()->json(['inquiry' => $inquiry], 201);
    }

    /**
     * POST /admin/api/inquiries/{id}/clone — duplicate a quote as a fresh one:
     * carries over the customer + job details, but resets the lifecycle
     * (status, scheduling, payment, signature) so it starts clean.
     */
    public function clone(string $id): JsonResponse
    {
        $source = Inquiry::find($id);
        if (! $source) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Only the customer's details carry over — the new quote's service/rental,
        // pricing, scheduling and notes start fresh.
        $copy = $source->only([
            'name', 'phone', 'email', 'address', 'zip_code', 'latitude', 'longitude',
            'preferred_contact_method', 'preferred_day', 'preferred_time',
        ]);
        $copy['service_type'] = 'other';   // job details are chosen on the new quote
        $copy['status'] = 'new';

        $inquiry = Inquiry::create($copy);

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
            'status', 'name', 'admin_notes', 'address', 'address_street', 'address_city', 'address_state',
            'confirmed_date_time', 'pickup_date_time', 'equipment_type',
            'equipment_rental_unit', 'phone', 'email', 'preferred_contact_method',
            'payment_method', 'payment_date', 'payment_notes', 'service_type',
            'zip_code', 'preferred_day', 'preferred_time', 'assigned_employee_id', 'pickup_assigned_employee_id', 'urgency',
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
        foreach (['equipment_rental_duration', 'expected_duration_minutes', 'pickup_duration_minutes'] as $k) {
            if (array_key_exists($k, $b)) {
                $updates[$k] = $b[$k] === null || $b[$k] === '' ? null : (int) $b[$k];
            }
        }
        // Multi-assignee arrays — store the JSON array and keep the legacy single
        // column in sync with the first (primary) assignee.
        foreach ([['assigned_employee_ids', 'assigned_employee_id'], ['pickup_assigned_employee_ids', 'pickup_assigned_employee_id']] as [$arrCol, $singleCol]) {
            if (array_key_exists($arrCol, $b)) {
                $ids = array_values(array_filter(array_map('strval', (array) $b[$arrCol]), fn ($v) => $v !== ''));
                $updates[$arrCol] = $ids;
                $updates[$singleCol] = $ids[0] ?? null;
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

        // These columns are NOT NULL — a blank value from the form arrives as null
        // (email is optional for admins), so store an empty string instead of failing.
        foreach (['name', 'phone', 'email', 'service_type'] as $req) {
            if (array_key_exists($req, $updates) && $updates[$req] === null) {
                $updates[$req] = '';
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
            'new' => (int) $new,
            'scheduled' => (int) $scheduled,
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

    /** POST /admin/api/inquiries/{id}/comments — add an internal/customer-visible comment. */
    public function comment(string $id, Request $request): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $body = trim((string) $request->input('body'));
        if ($body === '') {
            return response()->json(['error' => 'A comment is required.'], 422);
        }

        $comment = $inquiry->comments()->create([
            'author_id' => $request->session()->get('admin_id'),
            'author_name' => $request->session()->get('admin_username', 'admin'),
            'body' => $body,
            'customer_visible' => $request->boolean('customer_visible'),
        ]);

        return response()->json(['comment' => EmployeeCalendarController::commentPayload($comment)]);
    }

    /**
     * POST /admin/api/inquiries/{id}/rental-agreement
     * Generate (or reuse an active, unsigned) rental-agreement signing link the
     * admin can send to the customer at any point in the workflow.
     */
    public function agreement(string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Reuse a still-usable (unsigned, not cancelled, not expired) link;
        // otherwise mint a fresh one and log that an agreement was sent.
        $agreement = $inquiry->rentalAgreements()
            ->whereNull('signed_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('created_at')
            ->get()
            ->first(fn (RentalAgreement $a) => $a->isUsable());

        if (! $agreement) {
            $agreement = $inquiry->rentalAgreements()->create([
                'token' => (string) Str::uuid(),
                'form_data' => [],
            ]);
            $inquiry->logAudit('rental_agreement_sent');
        }

        return response()->json(['agreement' => self::agreementPayload($agreement)]);
    }

    public static function agreementPayload(RentalAgreement $a): array
    {
        return [
            'id' => $a->id,
            'token' => $a->token,
            'url' => route('rental-agreement.show', $a->token),
            'admin_url' => route('admin.rental-agreement.show', $a->id),
            'signed_at' => $a->signed_at,
            'cancelled_at' => $a->cancelled_at,
            'created_at' => $a->created_at,
            'usable' => $a->isUsable(),
            // Signature thumbnail (signed agreements only) for the admin panel.
            'signature_base64' => $a->signed_at ? $a->signature_base64 : null,
        ];
    }

    /**
     * POST /admin/api/inquiries/{id}/detail-request
     * Generate (or reuse) a one-time link the admin texts to the customer so they
     * can fill in the remaining quote details + confirm the schedule and amount.
     * Requires a visit date/time + a quoted price so there's something to confirm.
     */
    public function detailRequest(string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $hasSlot = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', (string) $inquiry->confirmed_date_time) === 1;
        if (! $hasSlot || (float) $inquiry->quoted_price <= 0) {
            return response()->json(['error' => 'Set a visit date/time and quoted price before requesting details.'], 422);
        }

        $request = $inquiry->detailRequests()
            ->whereNull('signed_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('created_at')
            ->get()
            ->first(fn (QuoteDetailRequest $d) => $d->isUsable());

        if (! $request) {
            $request = $inquiry->detailRequests()->create([
                'token' => (string) Str::uuid(),
                'form_data' => [],
            ]);
            $inquiry->logAudit('detail_request_sent');
        }

        return response()->json(['detail_request' => self::detailRequestPayload($request)]);
    }

    public static function detailRequestPayload(QuoteDetailRequest $d): array
    {
        return [
            'id' => $d->id,
            'token' => $d->token,
            'url' => route('quote-details.show', $d->token),
            'signed_at' => $d->signed_at,
            'cancelled_at' => $d->cancelled_at,
            'created_at' => $d->created_at,
            'usable' => $d->isUsable(),
            // Submitted data + signature (signed requests only) for the admin review panel.
            'form_data' => $d->signed_at ? $d->form_data : null,
            'signature_base64' => $d->signed_at ? $d->signature_base64 : null,
        ];
    }

    /** DELETE /admin/api/detail-request/{id} — remove a pending/used detail request. */
    public function detailRequestDestroy(string $id): JsonResponse
    {
        $req = QuoteDetailRequest::find($id);
        if (! $req) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $req->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /admin/api/inquiries/{id}/payment-link
     * Generate (or reuse) a payment link for the current quoted price. A still-
     * usable (unpaid, not cancelled, not expired) link is reused — its amount is
     * refreshed to the current quote in case it changed.
     */
    public function paymentLink(Request $request, string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $amount = $inquiry->quoted_price;
        // Field View may pass an amount when nothing was quoted yet — save it as the price.
        if (($amount === null || (float) $amount <= 0) && is_numeric($request->input('amount')) && (float) $request->input('amount') > 0) {
            $inquiry->update(['quoted_price' => (float) $request->input('amount')]);
            $amount = $inquiry->quoted_price;
        }
        if ($amount === null || (float) $amount <= 0) {
            return response()->json(['error' => 'Set a quoted price before sending a payment link.'], 422);
        }

        $link = $inquiry->paymentLinks()
            ->whereNull('paid_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('created_at')
            ->get()
            ->first(fn (PaymentLink $p) => $p->isUsable());

        if ($link) {
            $link->update(['amount' => $amount]); // keep the pending link in sync with the quote
        } else {
            $link = $inquiry->paymentLinks()->create([
                'token' => (string) Str::uuid(),
                'amount' => $amount,
            ]);
            $inquiry->logAudit('payment_link_sent');
        }

        return response()->json(['payment_link' => self::paymentLinkPayload($link)]);
    }

    public static function paymentLinkPayload(PaymentLink $p): array
    {
        return [
            'id' => $p->id,
            'token' => $p->token,
            'url' => route('payment.show', $p->token),
            'amount' => $p->amount,
            'paid_at' => $p->paid_at,
            'cancelled_at' => $p->cancelled_at,
            'payment_method' => $p->payment_method,
            'created_at' => $p->created_at,
            'usable' => $p->isUsable(),
        ];
    }
}
