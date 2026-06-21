<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = ServiceCatalog::active()->where('customer_visible', true)->orderBy('key')->get()
            ->map(fn (ServiceCatalog $s) => [
                'id' => $s->id,
                'key' => $s->key,
                'label' => $s->label,
                'default_price' => $s->default_price,
                'default_duration_minutes' => $s->default_duration_minutes,
            ]);

        return response()->json(['services' => $services]);
    }
}
