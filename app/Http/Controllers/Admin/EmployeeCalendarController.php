<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CapturesFieldPhotos;
use App\Http\Controllers\Concerns\CapturesFieldSignature;
use App\Http\Controllers\Concerns\EstimatesTravel;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryComment;
use App\Models\QuoteDetailRequest;
use App\Models\RentalAgreement;
use Illuminate\Http\Request;

class EmployeeCalendarController extends Controller
{
    use CapturesFieldPhotos;
    use CapturesFieldSignature;
    use EstimatesTravel;

    /** Statuses an employee may set on a job they're assigned to (field updates).
     *  Completion stays an admin step (after billing), so it's intentionally absent. */
    private const EMPLOYEE_STATUSES = ['service_performed', 'completed'];

    /** Calendar of the current user's assigned visits (day default, week toggle). */
    public function index(Request $request)
    {
        $me = $request->session()->get('admin_id');

        // Any scheduled visit/pickup where I'm one of the assignees (filtered in PHP
        // so it works the same on SQLite + MySQL regardless of JSON-query support).
        $rows = Inquiry::where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('confirmed_date_time')->orWhereNotNull('pickup_date_time'))
            ->orderBy('confirmed_date_time')
            ->get();

        $events = collect();
        foreach ($rows as $i) {
            if ($i->confirmed_date_time && in_array($me, $i->assigneeIds('visit'), true)) {
                $events->push(CalendarController::entry($i, 'visit'));
            }
            if ($i->pickup_date_time && in_array($me, $i->assigneeIds('pickup'), true)) {
                $events->push(CalendarController::entry($i, 'pickup'));
            }
        }

        return view('admin.my-schedule', ['events' => $events->values()]);
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

    /**
     * Serve a stored inquiry image (customer/field photo or signature) as a real
     * image file. The job sheet links to these instead of inlining base64, so the
     * HTML stays small — a page with megabytes of inline base64 exceeds the host
     * WAF's response-body limit and gets rejected as a 404.
     */
    public function jobImage(string $id, string $kind, int $index)
    {
        $inquiry = Inquiry::findOrFail($id);

        return $this->imageResponse(match ($kind) {
            'photos' => ($inquiry->photos ?? [])[$index] ?? null,
            'arrival' => ($inquiry->arrival_photos ?? [])[$index] ?? null,
            'departure' => ($inquiry->departure_photos ?? [])[$index] ?? null,
            'signature' => array_values($inquiry->signatures ?? [])[$index]['signature'] ?? null,
            'legacy' => $inquiry->photo_base64
                ? 'data:'.($inquiry->photo_mime ?: 'image/jpeg').';base64,'.$inquiry->photo_base64
                : null,
            default => null,
        });
    }

    /** Serve a rental-agreement or detail-request signature as an image file. */
    public function docImage(string $type, string $id)
    {
        $model = $type === 'agreement'
            ? RentalAgreement::findOrFail($id)
            : QuoteDetailRequest::findOrFail($id);

        return $this->imageResponse($model->signature_base64);
    }

    /** Decode a base64 image data URL into a real image response (or 404). */
    private function imageResponse(?string $dataUrl)
    {
        if (! is_string($dataUrl) || ! preg_match('#^data:(image/[\w.+-]+);base64,(.+)$#is', $dataUrl, $m)) {
            abort(404);
        }

        $binary = base64_decode($m[2], true);
        if ($binary === false) {
            abort(404);
        }

        return response($binary, 200, [
            'Content-Type' => $m[1],
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** Driving estimate from the employee's current location to their assigned job. */
    public function eta(Request $request, string $id)
    {
        return $this->travelEstimate($this->ownedInquiry($request, $id), $request);
    }

    public function etaSent(Request $request, string $id)
    {
        return $this->recordEtaSent($this->ownedInquiry($request, $id));
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
        $inquiry->update([$column => $request->boolean('clear') ? null : now()]);

        return redirect()->route('admin.my-schedule.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Add one or more photos to the arrival/departure log. */
    public function recordPhoto(Request $request, string $id, string $which)
    {
        $inquiry = $this->ownedInquiry($request, $id);
        $this->storeFieldPhotos($inquiry, $which, $request);

        return redirect()->route('admin.my-schedule.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Remove a photo from the arrival/departure log. */
    public function removePhoto(Request $request, string $id, string $which)
    {
        $inquiry = $this->ownedInquiry($request, $id);
        $this->removeFieldPhoto($inquiry, $which, (int) $request->input('index'));

        return redirect()->route('admin.my-schedule.job', $inquiry->id)->with('jobSaved', true);
    }

    /** Capture a per-action customer signature (service performed / equipment delivered / picked up). */
    public function sign(Request $request, string $id)
    {
        return $this->storeSignature($this->ownedInquiry($request, $id), $request);
    }

    /** Resolve an inquiry the current employee is assigned to (visit or pickup), or 404. */
    private function ownedInquiry(Request $request, string $id): Inquiry
    {
        $inquiry = Inquiry::find($id);
        $me = $request->session()->get('admin_id');
        $owns = $inquiry && (in_array($me, $inquiry->assigneeIds('visit'), true) || in_array($me, $inquiry->assigneeIds('pickup'), true));
        abort_unless($owns, 404);

        return $inquiry;
    }
}
