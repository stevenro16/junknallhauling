<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryComment;
use Illuminate\Http\Request;

class EmployeeCalendarController extends Controller
{
    /** Statuses an employee may set on a job they're assigned to (field updates).
     *  Completion stays an admin step (after billing), so it's intentionally absent. */
    private const EMPLOYEE_STATUSES = ['service_performed'];

    /** Calendar of the current user's assigned visits (day default, week toggle). */
    public function index(Request $request)
    {
        $me = $request->session()->get('admin_id');

        $rows = Inquiry::where('assigned_employee_id', $me)
            ->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('confirmed_date_time')->orWhereNotNull('pickup_date_time'))
            ->with('assignedEmployee:id,username')
            ->orderBy('confirmed_date_time')
            ->get();

        return view('admin.my-schedule', ['events' => CalendarController::calendarEntries($rows)]);
    }

    /** Job sheet for an assigned visit (full detail + comments). */
    public function job(Request $request, string $id)
    {
        $inquiry = $this->ownedInquiry($request, $id);

        return view('admin.employee-job', [
            'inquiry' => $inquiry,
            'comments' => $inquiry->comments()->orderBy('created_at')->get()
                ->map(fn (InquiryComment $c) => self::commentPayload($c))->values(),
        ]);
    }

    /** Add an internal (or customer-visible) comment to an assigned job. */
    public function addComment(Request $request, string $id)
    {
        $inquiry = $this->ownedInquiry($request, $id);

        $body = trim((string) $request->input('body'));
        if ($body === '') {
            return response()->json(['error' => 'A comment is required.'], 422);
        }

        $comment = $inquiry->comments()->create([
            'author_id' => $request->session()->get('admin_id'),
            'author_name' => $request->session()->get('admin_username', 'employee'),
            'body' => $body,
            'customer_visible' => $request->boolean('customer_visible'),
        ]);

        return response()->json(['comment' => self::commentPayload($comment)]);
    }

    /** Shared JSON shape for a comment (used by the admin side too). */
    public static function commentPayload(InquiryComment $c): array
    {
        return [
            'id' => $c->id,
            'author_name' => $c->author_name,
            'body' => $c->body,
            'customer_visible' => (bool) $c->customer_visible,
            'created_at' => $c->created_at?->toISOString(),
        ];
    }

    /** Let the assigned employee advance the job status from the field. */
    public function updateStatus(Request $request, string $id)
    {
        $inquiry = $this->ownedInquiry($request, $id);
        $new = (string) $request->input('status');

        if (! in_array($new, self::EMPLOYEE_STATUSES, true)) {
            return back()->with('jobError', 'That status is not allowed.');
        }

        $old = $inquiry->status;
        if ($new !== $old) {
            $inquiry->update(['status' => $new]);
            $inquiry->logStatusChange($old, $new, $request->session()->get('admin_username', 'employee'));
        }

        return redirect()->route('admin.my-schedule.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Stamp the arrival or departure time on an assigned visit. */
    public function recordTime(Request $request, string $id, string $which)
    {
        $inquiry = $this->ownedInquiry($request, $id);

        $column = $which === 'arrival' ? 'arrived_at' : 'departed_at';
        $inquiry->update([$column => now()]);

        return redirect()->route('admin.my-schedule.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Store the customer's signature for a completed visit (and mark it completed). */
    public function sign(Request $request, string $id)
    {
        $inquiry = $this->ownedInquiry($request, $id);

        $signature = (string) $request->input('signature');
        if (! str_starts_with($signature, 'data:image/')) {
            return response()->json(['error' => 'A signature is required.'], 422);
        }

        $inquiry->update([
            'service_signature' => $signature,
            'service_signed_at' => now(),
        ]);

        // Capturing the customer's signature marks the service performed, so the
        // admin can send the bill. (Completion stays an admin step, post-billing.)
        if (! in_array($inquiry->status, ['service_performed', 'completed'], true)) {
            $old = $inquiry->status;
            $inquiry->update(['status' => 'service_performed']);
            $inquiry->logStatusChange($old, 'service_performed', $request->session()->get('admin_username', 'employee'));
        }

        return response()->json(['success' => true]);
    }

    /** Resolve an inquiry that belongs to the current employee, or 404. */
    private function ownedInquiry(Request $request, string $id): Inquiry
    {
        $inquiry = Inquiry::find($id);
        abort_unless($inquiry && $inquiry->assigned_employee_id === $request->session()->get('admin_id'), 404);

        return $inquiry;
    }
}
