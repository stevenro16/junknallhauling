<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;

class CalendarController extends Controller
{
    public function index()
    {
        $events = Inquiry::whereNotNull('confirmed_date_time')
            ->where('status', '!=', 'cancelled')
            ->orderBy('confirmed_date_time')
            ->get()
            ->map(fn (Inquiry $i) => [
                'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
                'service_type' => $i->service_type, 'address' => $i->address,
                'confirmed_date_time' => $i->confirmed_date_time,
                'expected_duration_minutes' => $i->expected_duration_minutes ?? 120,
            ])->values();

        return view('admin.calendar', ['events' => $events]);
    }
}
