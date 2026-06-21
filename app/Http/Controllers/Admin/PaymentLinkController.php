<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use Illuminate\Http\JsonResponse;

class PaymentLinkController extends Controller
{
    /** Delete a payment link — a pending one or a completed record. */
    public function destroy(string $id): JsonResponse
    {
        $link = PaymentLink::find($id);
        if (! $link) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $link->delete();

        return response()->json(['ok' => true]);
    }
}
