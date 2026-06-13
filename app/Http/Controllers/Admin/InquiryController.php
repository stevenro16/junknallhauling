<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use App\Models\Inquiry;

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
            'inquiry'        => $inquiry,
            'history'        => $inquiry->statusHistory()->orderByDesc('changed_at')->get(),
            'equipment'      => EquipmentType::active()->orderBy('name')->get(),
            // Lightweight list for the "previous customer addresses" feature.
            'allInquiries'   => Inquiry::orderByDesc('created_at')->get([
                'id', 'phone', 'email', 'address', 'created_at',
            ]),
        ]);
    }
}
