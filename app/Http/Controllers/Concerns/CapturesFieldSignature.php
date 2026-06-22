<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait CapturesFieldSignature
{
    /** Field-action statuses that require a captured customer signature. */
    private const SIGNATURE_STATUSES = ['service_performed', 'equipment_delivered', 'equipment_picked_up'];

    /**
     * Store a per-action customer signature and advance the job to that status.
     * `signatures` keeps one entry per action; `service_signature` holds the latest.
     */
    protected function storeSignature(Inquiry $inquiry, Request $request): JsonResponse
    {
        $signature = (string) $request->input('signature');
        if (! str_starts_with($signature, 'data:image/')) {
            return response()->json(['error' => 'A signature is required.'], 422);
        }

        $status = (string) $request->input('status', 'service_performed');
        if (! in_array($status, self::SIGNATURE_STATUSES, true)) {
            $status = 'service_performed';
        }

        $now = now();
        $sigs = $inquiry->signatures ?? [];
        $sigs[$status] = ['signature' => $signature, 'signed_at' => $now->toISOString()];
        $inquiry->update([
            'signatures' => $sigs,
            'service_signature' => $signature,   // latest captured (legacy display)
            'service_signed_at' => $now,
        ]);

        if ($inquiry->status !== $status && $inquiry->status !== 'completed') {
            $old = $inquiry->status;
            $inquiry->update(['status' => $status]);
            $inquiry->logStatusChange($old, $status, $request->session()->get('admin_username', 'field'));
        }

        return response()->json(['success' => true]);
    }
}
