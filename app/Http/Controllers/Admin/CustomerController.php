<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;

class CustomerController extends Controller
{
    /** Customer directory — search by phone/email, then per-customer analytics + quotes. */
    public function index()
    {
        // Slim list (no base64 photos); the page groups these into customers and
        // computes per-customer analytics client-side.
        $inquiries = Inquiry::orderByDesc('created_at')->get()->map(fn (Inquiry $i) => [
            'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'phone' => $i->phone, 'email' => $i->email,
            'status' => $i->status, 'service_type' => $i->service_type, 'equipment_type' => $i->equipment_type,
            'equipment_rental_duration' => $i->equipment_rental_duration, 'equipment_rental_unit' => $i->equipment_rental_unit,
            'quoted_price' => $i->quoted_price, 'payment_method' => $i->payment_method,
            'confirmed_date_time' => $i->confirmed_date_time, 'created_at' => $i->created_at,
            'address' => $i->address, 'zip_code' => $i->zip_code, 'preferred_contact_method' => $i->preferred_contact_method,
        ])->values();

        return view('admin.customers', ['inquiries' => $inquiries]);
    }
}
