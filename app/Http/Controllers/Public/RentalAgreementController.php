<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class RentalAgreementController extends Controller
{
    /**
     * Public signing page. Prefill + validity are resolved client-side via
     * GET /api/rental-agreement/{token} (mirrors the Next.js page).
     */
    public function show(string $token)
    {
        return view('public.rental-agreement', ['token' => $token]);
    }
}
