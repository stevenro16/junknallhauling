<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\RentalAgreement;

class RentalAgreementController extends Controller
{
    /**
     * Public signing page. Prefill + validity are resolved client-side via
     * GET /api/rental-agreement/{token}; the agreement terms are resolved here
     * (snapshot if already signed, else the current template) and rendered server-side.
     */
    public function show(string $token)
    {
        $agreement = RentalAgreement::where('token', $token)->first();

        return view('public.rental-agreement', [
            'token' => $token,
            'content' => $agreement
                ? $agreement->effectiveContent()
                : ['title' => 'Agreement', 'acknowledgments' => [], 'instructions' => null],
        ]);
    }
}
