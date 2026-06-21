<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Customer self-service lookup by phone + email.
     * Mirrors getPublicInquiriesByPhoneAndEmail().
     */
    public function index(Request $request): JsonResponse
    {
        $phone = (string) $request->query('phone', '');
        $email = (string) $request->query('email', '');

        if ($phone === '' || $email === '') {
            return response()->json(['error' => 'Phone and email are required'], 400);
        }

        $normalized = preg_replace('/\D/', '', $phone);
        $tail = substr($normalized, -10);

        $inquiries = Inquiry::where('email', $email)
            ->whereRaw("REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', '') LIKE ?", ['%'.$tail.'%'])
            // Only customer-visible comments are exposed publicly — never internal ones.
            ->with(['comments' => fn ($q) => $q->where('customer_visible', true)->orderBy('created_at')])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['inquiries' => $inquiries]);
    }
}
