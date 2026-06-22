<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryComment;
use Illuminate\Http\Request;

/**
 * Admin "Field View" — the same field experience employees get (arrival/departure
 * stamps, customer signature, mark service performed) but across ALL scheduled jobs,
 * plus admin-only extras (send a payment link, jump to the full quote). Reuses the
 * employee calendar + job-sheet views and the shared comment/entry/payment helpers.
 */
class FieldViewController extends Controller
{
    /** Calendar of every scheduled visit + equipment pickup (admin oversight). */
    public function index()
    {
        $rows = Inquiry::where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('confirmed_date_time')->orWhereNotNull('pickup_date_time'))
            ->with(['assignedEmployee:id,username', 'pickupAssignedEmployee:id,username'])
            ->orderBy('confirmed_date_time')
            ->get();

        $events = collect();
        foreach ($rows as $i) {
            if ($i->confirmed_date_time) {
                $events->push(CalendarController::entry($i, 'visit'));
            }
            if ($i->pickup_date_time) {
                $events->push(CalendarController::entry($i, 'pickup'));
            }
        }

        return view('admin.my-schedule', [
            'events' => $events->values(),
            'title' => 'Field View',
            'subtitle' => 'Document arrivals, signatures & service for any scheduled job',
            'detailRoute' => 'admin.field.job',
            'unitNoun' => 'scheduled job',
            'emptyText' => 'No scheduled jobs.',
        ]);
    }

    /** Field job sheet for any inquiry, with the admin payment + full-quote extras. */
    public function job(string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        return view('admin.employee-job', [
            'inquiry' => $inquiry,
            'comments' => $inquiry->comments()->orderBy('created_at')->get()
                ->map(fn (InquiryComment $c) => EmployeeCalendarController::commentPayload($c))->values(),
            'routeBase' => 'admin.field',
            'backRoute' => 'admin.field',
            'backLabel' => 'Back to field view',
            'adminField' => true,
            'paymentLinks' => $inquiry->paymentLinks()->orderByDesc('created_at')->get()
                ->map(fn ($p) => InquiryApiController::paymentLinkPayload($p))->values(),
        ]);
    }

    /** Add an internal (or customer-visible) comment to a job. */
    public function addComment(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $body = trim((string) $request->input('body'));
        if ($body === '') {
            return response()->json(['error' => 'A comment is required.'], 422);
        }

        $comment = $inquiry->comments()->create([
            'author_id' => $request->session()->get('admin_id'),
            'author_name' => $request->session()->get('admin_username', 'admin'),
            'body' => $body,
            'customer_visible' => $request->boolean('customer_visible'),
        ]);

        return response()->json(['comment' => EmployeeCalendarController::commentPayload($comment)]);
    }

    /** Set the job status from the field. Admin Field View, so any valid status is allowed. */
    public function updateStatus(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $new = (string) $request->input('status');

        if (! array_key_exists($new, config('business.status_labels'))) {
            return back()->with('jobError', 'That status is not allowed.');
        }

        $old = $inquiry->status;
        if ($new !== $old) {
            $inquiry->update(['status' => $new]);
            $inquiry->logStatusChange($old, $new, $request->session()->get('admin_username', 'admin'));
        }

        return redirect()->route('admin.field.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Stamp arrival or departure time on the visit. */
    public function recordTime(Request $request, string $id, string $which)
    {
        $inquiry = Inquiry::findOrFail($id);

        $column = $which === 'arrival' ? 'arrived_at' : 'departed_at';
        $inquiry->update([$column => now()]);

        return redirect()->route('admin.field.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Store the customer's signature (and mark the service performed, ready to bill). */
    public function sign(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $signature = (string) $request->input('signature');
        if (! str_starts_with($signature, 'data:image/')) {
            return response()->json(['error' => 'A signature is required.'], 422);
        }

        $inquiry->update([
            'service_signature' => $signature,
            'service_signed_at' => now(),
        ]);

        if (! in_array($inquiry->status, ['service_performed', 'completed'], true)) {
            $old = $inquiry->status;
            $inquiry->update(['status' => 'service_performed']);
            $inquiry->logStatusChange($old, 'service_performed', $request->session()->get('admin_username', 'admin'));
        }

        return response()->json(['success' => true]);
    }
}
