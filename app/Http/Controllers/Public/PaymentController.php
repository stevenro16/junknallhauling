<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    /**
     * Public payment page. Prefill + validity are resolved client-side via
     * GET /api/payment/{token} (mirrors the rental-agreement page).
     */
    public function show(string $token)
    {
        return view('public.payment', ['token' => $token]);
    }
}
