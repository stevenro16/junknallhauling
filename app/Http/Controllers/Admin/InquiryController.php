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

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'history' => $inquiry->statusHistory()->orderByDesc('changed_at')->get(),
            'equipment' => EquipmentType::active()->orderBy('name')->get(),
            // Service-catalog options for the Job Details picker (the dedicated
            // 'equipment' entry is excluded — that's the Equipment Rental pill).
            'services' => ServiceCatalog::active()->where('key', '!=', 'equipment')->orderBy('label')->get(),
            // Employees the quote can be assigned to.
            'employees' => Admin::where('role', 'employee')->orderBy('username')->get(['id', 'username']),
            // Threaded comments (internal + customer-visible).
            'comments' => $inquiry->comments()->orderBy('created_at')->get()
                ->map(fn ($c) => EmployeeCalendarController::commentPayload($c))->values(),
            'agreements' => $inquiry->rentalAgreements()->orderByDesc('created_at')->get()
                ->map(fn ($a) => InquiryApiController::agreementPayload($a))->values(),
            'paymentLinks' => $inquiry->paymentLinks()->orderByDesc('created_at')->get()
                ->map(fn ($p) => InquiryApiController::paymentLinkPayload($p))->values(),
            // Lightweight list powering "previous customer addresses" + the
            // "pull customer info from a prior order" feature (matched on phone/email).
            'allInquiries' => Inquiry::orderByDesc('created_at')->get([
                'id', 'ref', 'name', 'phone', 'email', 'address', 'zip_code',
                'preferred_contact_method', 'preferred_day', 'preferred_time', 'created_at',
            ]),
            // Confirmed visits for the in-form day-schedule panel (scheduling context).
            'scheduleEvents' => Inquiry::whereNotNull('confirmed_date_time')
                ->where('status', '!=', 'cancelled')
                ->with('assignedEmployee:id,username')
                ->orderBy('confirmed_date_time')
                ->get(['id', 'ref', 'name', 'status', 'service_type', 'address', 'confirmed_date_time', 'expected_duration_minutes', 'assigned_employee_id'])
                ->map(fn (Inquiry $i) => [
                    'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
                    'service_type' => $i->service_type, 'address' => $i->address,
                    'confirmed_date_time' => $i->confirmed_date_time,
                    'expected_duration_minutes' => $i->expected_duration_minutes,
                    'assigned_employee' => $i->assignedEmployee?->username,
                ])->values(),
        ]);
    }
}
