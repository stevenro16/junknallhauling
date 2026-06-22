<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CapturesFieldSignature;
use App\Http\Controllers\Concerns\EstimatesTravel;
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
    use CapturesFieldSignature;
    use EstimatesTravel;

    /** Driving estimate from the admin's current location to this job's address. */
    public function eta(Request $request, string $id)
    {
        return $this->travelEstimate(Inquiry::findOrFail($id), $request);
    }

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

    /** Record an in-field payment (cash/check/card/Venmo/…) and mark the job paid. */
    public function recordPayment(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $method = trim((string) $request->input('payment_method'));
        if ($method === '') {
            return response()->json(['error' => 'Select how the customer paid.'], 422);
        }

        $inquiry->update([
            'payment_method' => $method,
            'payment_date' => now()->format('Y-m-d\TH:i'),
        ]);

        // Settle any still-open payment link so it doesn't linger as "awaiting payment".
        $inquiry->paymentLinks()->whereNull('paid_at')->whereNull('cancelled_at')
            ->update(['paid_at' => now(), 'payment_method' => $method]);

        $inquiry->logAudit('payment_received');

        return response()->json([
            'payment_method' => $inquiry->payment_method,
            'payment_date' => $inquiry->payment_date,
        ]);
    }

    /** Store the customer's signature (and mark the service performed, ready to bill). */
    public function sign(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        return $this->storeSignature($inquiry, $request);
    }
}
