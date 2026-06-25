<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalAgreement;

class RentalAgreementController extends Controller
{
    /** Read-only admin view of a rental agreement (signed or pending). */
    public function show(string $id)
    {
        $agreement = RentalAgreement::with('inquiry', 'agreement')->find($id);
        if (! $agreement) {
            abort(404);
        }

        return view('admin.rental-agreement', [
            'agreement' => $agreement,
            'content' => $agreement->effectiveContent(),
        ]);
    }

    /** Delete a rental agreement — a pending link or a signed/collected one. */
    public function destroy(string $id)
    {
        $agreement = RentalAgreement::find($id);
        if (! $agreement) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $agreement->delete();

        return response()->json(['ok' => true]);
    }
}
