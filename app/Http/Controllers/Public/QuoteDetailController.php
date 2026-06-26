<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QuoteDetailRequest;

class QuoteDetailController extends Controller
{
    /**
     * Public "request details" page. Prefill + validity are resolved client-side
     * via GET /api/quote-details/{token}. When the linked quote needs a rental
     * agreement, its terms are resolved here and rendered inline so the customer
     * completes everything (details + agreement) on one form.
     */
    public function show(string $token)
    {
        $inquiry = QuoteDetailRequest::where('token', $token)->first()?->inquiry;
        $needsAgreement = $inquiry?->needsAgreement() ?? false;

        $agreement = null;
        if ($needsAgreement && ($template = $inquiry->agreementTemplate())) {
            $agreement = [
                'title' => $template->title,
                'acknowledgments' => $template->acknowledgments ?? [],
                'instructions' => $template->instructions,
            ];
        }

        return view('public.quote-details', [
            'token' => $token,
            'needsAgreement' => $needsAgreement,
            'agreement' => $agreement,
        ]);
    }
}
