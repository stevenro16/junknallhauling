<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mirrors the Zod quoteSchema from the Next.js app.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'phone' => 'required|string|min:10',
            'email' => 'required|email',
            'service_type' => 'required|in:junk-removal,10yd-dumpster,20yd-dumpster,equipment,other',
            'description' => 'nullable|string',
            'photo_base64' => 'nullable|string',
            'photo_mime' => 'nullable|string',
            'website' => 'nullable|string',   // honeypot
            'zip_code' => 'required|string|min:5',
            'preferred_day' => 'nullable|string',
            'preferred_time' => 'nullable|string',
            'equipment_type' => 'nullable|string',
            'equipment_rental_duration' => 'nullable|integer|min:1',
            'equipment_rental_unit' => 'nullable|in:hours,days',
            'initial_estimated_quote' => 'nullable|numeric|min:0',
            'preferred_contact_method' => 'nullable|in:phone,email',
            'urgency' => 'nullable|in:routine,urgent',
        ];
    }
}
