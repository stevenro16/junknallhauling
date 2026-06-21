<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'history' => $inquiry->statusHistory()->orderByDesc('changed_at')->get(),
            'equipment' => EquipmentType::active()->orderBy('name')->get(),
            // Service-catalog options for the Job Details picker (the dedicated
            // 'equipment' entry is excluded — that's the Equipment Rental pill).
            'services' => ServiceCatalog::active()->where('key', '!=', 'equipment')->orderBy('label')->get(),
            'agreements' => $inquiry->rentalAgreements()->orderByDesc('created_at')->get()
                ->map(fn ($a) => InquiryApiController::agreementPayload($a))->values(),
            'paymentLinks' => $inquiry->paymentLinks()->orderByDesc('created_at')->get()
                ->map(fn ($p) => InquiryApiController::paymentLinkPayload($p))->values(),
            // Lightweight list for the "previous customer addresses" feature.
            'allInquiries' => Inquiry::orderByDesc('created_at')->get([
                'id', 'phone', 'email', 'address', 'created_at',
            ]),
            // Confirmed visits for the in-form day-schedule panel (scheduling context).
            'scheduleEvents' => Inquiry::whereNotNull('confirmed_date_time')
                ->where('status', '!=', 'cancelled')
                ->orderBy('confirmed_date_time')
                ->get(['id', 'ref', 'name', 'status', 'service_type', 'confirmed_date_time', 'expected_duration_minutes'])
                ->values(),
        ]);
    }
}
