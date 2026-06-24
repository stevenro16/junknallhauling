<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        return view('admin.calendar', [
            'events' => $this->events(),
            'employees' => InquiryController::assignees(),
        ]);
    }

    /** Quick-create a quote from a clicked calendar slot (phone required; pre-fills the
     *  visit date/time + assignee). The admin lands on the editor to fill in the rest. */
    public function quickQuote(Request $request)
    {
        $phone = trim((string) $request->input('phone'));
        if ($phone === '') {
            return response()->json(['error' => 'A phone number is required.'], 422);
        }

        $data = [
            'phone' => $phone, 'name' => '', 'email' => '', 'service_type' => 'other', 'zip_code' => '',
            'status' => 'quoted',   // admin-created quotes start as Quoted (even from a calendar slot)
        ];

        $datetime = (string) $request->input('datetime');
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $datetime)) {
            $data['confirmed_date_time'] = $datetime;
            $data['expected_duration_minutes'] = 120;
        }

        $employeeId = trim((string) $request->input('employee_id'));
        if ($employeeId !== '') {
            $data['assigned_employee_ids'] = [$employeeId];
            $data['assigned_employee_id'] = $employeeId;
        }

        $inquiry = Inquiry::create($data);

        return response()->json(['inquiry' => ['id' => $inquiry->id]], 201);
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
            'target' => $request->query('target') === 'pickup' ? 'pickup' : 'visit',
            'assignee' => (string) $request->query('assignee', ''),
            'assigneeName' => (string) $request->query('assignee_name', ''),
            'employees' => InquiryController::assignees(),
        ]);
    }

    /** Non-cancelled visits + equipment pickups shaped for the calendar component. */
    private function events()
    {
        $rows = Inquiry::where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('confirmed_date_time')->orWhereNotNull('pickup_date_time'))
            ->with(['assignedEmployee:id,username', 'pickupAssignedEmployee:id,username'])
            ->orderBy('confirmed_date_time')
            ->get();

        return self::calendarEntries($rows);
    }

    /**
     * Flatten inquiries into calendar entries: one for the delivery/visit
     * (confirmed_date_time) and, for equipment rentals, one for the pickup
     * (pickup_date_time). Shared with the employee schedule.
     */
    public static function calendarEntries($rows)
    {
        $events = [];
        foreach ($rows as $i) {
            if ($i->confirmed_date_time) {
                $events[] = self::entry($i, 'visit');
            }
            if ($i->pickup_date_time) {
                $events[] = self::entry($i, 'pickup');
            }
        }

        return collect($events)->values();
    }

    /** One calendar entry for an inquiry's visit or pickup (datetime/duration/assignee by type). */
    public static function entry(Inquiry $i, string $type): array
    {
        $isPickup = $type === 'pickup';
        $dt = $isPickup ? $i->pickup_date_time : $i->confirmed_date_time;
        $duration = $isPickup ? ($i->pickup_duration_minutes ?: 60) : ($i->expected_duration_minutes ?? 120);
        $assigneeIds = $i->assigneeIds($type);
        $employee = Admin::namesFor($assigneeIds);

        // Flag a pickup scheduled before the delivery visit (illogical → warn).
        $beforeVisit = $isPickup && $i->confirmed_date_time && $i->pickup_date_time
            && Carbon::parse($i->pickup_date_time)->lt(Carbon::parse($i->confirmed_date_time));

        return [
            'event_id' => $isPickup ? $i->id.':pickup' : $i->id,
            'id' => $i->id,
            'type' => $type,
            'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
            'service_type' => $i->service_type, 'equipment_type' => $i->equipment_type, 'address' => $i->address,
            'confirmed_date_time' => $dt,   // the datetime for this entry (visit or pickup)
            'expected_duration_minutes' => $duration,
            'assigned_employee' => $employee,
            'assignee_id' => $assigneeIds[0] ?? null,   // primary (legacy single-filter)
            'assignee_ids' => $assigneeIds,             // all assignees (multi filter/columns)
            'before_visit' => $beforeVisit,
        ];
    }
}
