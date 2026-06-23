<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class QuoteDetailController extends Controller
{
    /**
     * Public "request details" page. Prefill + validity are resolved client-side
     * via GET /api/quote-details/{token} (mirrors the rental-agreement page).
     */
    public function show(string $token)
    {
        return view('public.quote-details', ['token' => $token]);
    }
}
