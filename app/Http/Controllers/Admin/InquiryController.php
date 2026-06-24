<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\EquipmentType;
use App\Models\Inquiry;
use App\Models\ServiceCatalog;

class InquiryController extends Controller
{
    /** Quote detail page (/admin/inquiries/{id}). */
    public function show(string $id)
    {
        $inquiry = Inquiry::find($id);
        if (! $inquiry) {
            abort(404);
        }

        // Opening a brand-new quote moves it into review automatically.
        if ($inquiry->status === 'new') {
            $inquiry->update(['status' => 'reviewing']);
            $inquiry->logStatusChange('new', 'reviewing');
        }

        // Keep the bulky base64 signature out of the Alpine (@js) payload — it's
        // rendered server-side in the read-only Service Visit summary instead.
        $inquiry->makeHidden('service_signature');

        // Pickup time the customer requested on a signed rental agreement — surfaced
        // so the admin can schedule the pickup when one isn't set yet.
        $customerPickup = null;
        foreach ($inquiry->rentalAgreements()->whereNotNull('signed_at')->orderByDesc('signed_at')->get() as $a) {
            $fd = $a->form_data ?? [];
            if (! empty($fd['pickup_date']) || ! empty($fd['pickup_time'])) {
                $customerPickup = [
                    'date' => $fd['pickup_date'] ?? null,
                    'time' => $fd['pickup_time'] ?? null,       // formatted, for display
                    'time24' => $fd['pickup_time_24'] ?? null,  // HH:MM, for applying to the field
                    'signed_at' => $a->signed_at,
                ];
                break;
            }
        }

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'history' => $inquiry->statusHistory()->orderByDesc('changed_at')->get(),
            'equipment' => EquipmentType::active()->orderBy('name')->get(),
            // Service-catalog options for the Job Details picker (the dedicated
            // 'equipment' entry is excluded — that's the Equipment Rental pill).
            'services' => ServiceCatalog::active()->where('key', '!=', 'equipment')->orderBy('label')->get(),
            // People the quote/pickup can be assigned to: employees + the current
            // admin (so they can put themselves on a job/pickup).
            'employees' => self::assignees(),
            // Threaded comments (internal + customer-visible).
            'comments' => $inquiry->comments()->orderBy('created_at')->get()
                ->map(fn ($c) => EmployeeCalendarController::commentPayload($c))->values(),
            'customerPickup' => $customerPickup,
            'agreements' => $inquiry->rentalAgreements()->orderByDesc('created_at')->get()
                ->map(fn ($a) => InquiryApiController::agreementPayload($a))->values(),
            'paymentLinks' => $inquiry->paymentLinks()->orderByDesc('created_at')->get()
                ->map(fn ($p) => InquiryApiController::paymentLinkPayload($p))->values(),
            'detailRequests' => $inquiry->detailRequests()->orderByDesc('created_at')->get()
                ->map(fn ($d) => InquiryApiController::detailRequestPayload($d))->values(),
            // Lightweight list powering "previous customer addresses" + the
            // "pull customer info from a prior order" feature (matched on phone/email).
            'allInquiries' => Inquiry::orderByDesc('created_at')->get([
                'id', 'ref', 'name', 'phone', 'email', 'address', 'address_street', 'address_city', 'address_state', 'zip_code',
                'preferred_contact_method', 'preferred_day', 'preferred_time', 'created_at',
            ]),
            // Confirmed visits for the in-form day-schedule panel (scheduling context).
            'scheduleEvents' => Inquiry::whereNotNull('confirmed_date_time')
                ->where('status', '!=', 'cancelled')
                ->orderBy('confirmed_date_time')
                ->get(['id', 'ref', 'name', 'status', 'service_type', 'address', 'confirmed_date_time', 'expected_duration_minutes', 'assigned_employee_id', 'assigned_employee_ids'])
                ->map(fn (Inquiry $i) => [
                    'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
                    'service_type' => $i->service_type, 'address' => $i->address,
                    'confirmed_date_time' => $i->confirmed_date_time,
                    'expected_duration_minutes' => $i->expected_duration_minutes,
                    'assigned_employee' => Admin::namesFor($i->assigneeIds('visit')),
                    'assigned_employee_ids' => $i->assigneeIds('visit'),
                ])->values(),
        ]);
    }

    /** Print-friendly "Detailed Report" for a quote (browser → Save as PDF). */
    public function report(string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        return view('admin.inquiries.report', [
            'inquiry' => $inquiry,
            'history' => $inquiry->statusHistory()->orderBy('changed_at')->get(),
            'agreements' => $inquiry->rentalAgreements()->whereNotNull('signed_at')->orderBy('signed_at')->get(),
            'detailRequests' => $inquiry->detailRequests()->whereNotNull('signed_at')->orderBy('signed_at')->get(),
            'paymentLinks' => $inquiry->paymentLinks()->orderByDesc('created_at')->get(),
            'comments' => $inquiry->comments()->orderBy('created_at')->get(),
            'visitAssignees' => Admin::namesFor($inquiry->assigneeIds('visit')),
            'pickupAssignees' => Admin::namesFor($inquiry->assigneeIds('pickup')),
        ]);
    }

    /** Employees + the current admin (labelled "(me)") — who a job or pickup can be assigned to. */
    public static function assignees()
    {
        $list = Admin::where('role', 'employee')->orderBy('username')->get(['id', 'username'])
            ->map(fn (Admin $e) => ['id' => $e->id, 'username' => $e->username, 'label' => $e->username]);

        $me = Admin::find(session('admin_id'));
        if ($me) {
            $list->push(['id' => $me->id, 'username' => $me->username, 'label' => $me->username.' (me)']);
        }

        return $list->values();
    }
}
