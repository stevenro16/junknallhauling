<?php

namespace App\Http\Requests;

use App\Models\ServiceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Allowed service types track the editable Service Catalog (the source of
        // truth shown in the form), plus the special 'equipment'/'other' options
        // and the built-in fallback keys used before the catalog loads. This keeps
        // admin-added services (e.g. "moving") from being rejected at submit.
        $serviceTypes = ServiceCatalog::pluck('key')
            ->merge(['junk-removal', '10yd-dumpster', '20yd-dumpster', 'equipment', 'help-me-decide', 'other'])
            ->unique()
            ->values()
            ->all();

        return [
            'name' => 'required|string|min:2',
            'phone' => 'required|string|min:10',
            'email' => 'required|email',
            'service_type' => ['required', 'string', Rule::in($serviceTypes)],
            'description' => 'nullable|string',
            'photo_base64' => 'nullable|string',
            'photo_mime' => 'nullable|string',
            'photos' => 'nullable|array|max:3',     // "Help Me Decide" project photos
            'photos.*' => 'string',
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
