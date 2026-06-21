<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('admin.calendar', ['events' => $this->events()]);
    }

    /** Compact, bare day view for embedding in the quote page's calendar popup. */
    public function embed(Request $request)
    {
        return view('admin.calendar-embed', [
            'events' => $this->events(),
            'date' => (string) $request->query('date', ''),
            'time' => (string) $request->query('time', ''),
            'duration' => max(15, (int) $request->query('duration', 120)),
            'label' => (string) $request->query('label', 'This visit'),
        ]);
    }

    /** Confirmed, non-cancelled visits shaped for the calendar component. */
    private function events()
    {
        return Inquiry::whereNotNull('confirmed_date_time')
            ->where('status', '!=', 'cancelled')
            ->orderBy('confirmed_date_time')
            ->get()
            ->map(fn (Inquiry $i) => [
                'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
                'service_type' => $i->service_type, 'address' => $i->address,
                'confirmed_date_time' => $i->confirmed_date_time,
                'expected_duration_minutes' => $i->expected_duration_minutes ?? 120,
            ])->values();
    }
}
