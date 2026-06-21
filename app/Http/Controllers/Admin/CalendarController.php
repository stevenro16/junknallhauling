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
            'exclude' => (string) $request->query('exclude', ''),
        ]);
    }

    /** Non-cancelled visits + equipment pickups shaped for the calendar component. */
    private function events()
    {
        $rows = Inquiry::where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('confirmed_date_time')->orWhereNotNull('pickup_date_time'))
            ->with('assignedEmployee:id,username')
            ->orderBy('confirmed_date_time')
            ->get();

        return self::calendarEntries($rows);
    }

    /**
     * Flatten inquiries into calendar entries: one for the delivery/visit
     * (confirmed_date_time) and, for equipment rentals, one for the pickup
     * (pickup_date_time, 1-hour block). Shared with the employee schedule.
     */
    public static function calendarEntries($rows)
    {
        $events = [];
        foreach ($rows as $i) {
            if ($i->confirmed_date_time) {
                $events[] = self::entry($i, 'visit', $i->confirmed_date_time, $i->expected_duration_minutes ?? 120);
            }
            if ($i->pickup_date_time) {
                $events[] = self::entry($i, 'pickup', $i->pickup_date_time, 60);
            }
        }

        return collect($events)->values();
    }

    private static function entry(Inquiry $i, string $type, string $dt, int $duration): array
    {
        return [
            'event_id' => $type === 'pickup' ? $i->id.':pickup' : $i->id,
            'id' => $i->id,
            'type' => $type,
            'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
            'service_type' => $i->service_type, 'equipment_type' => $i->equipment_type, 'address' => $i->address,
            'confirmed_date_time' => $dt,   // the datetime for this entry (visit or pickup)
            'expected_duration_minutes' => $duration,
            'assigned_employee' => $i->assignedEmployee?->username,
        ];
    }
}
