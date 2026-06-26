<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        $equipment = EquipmentType::active()->where('customer_visible', true)->orderBy('name')->get()
            ->map(fn (EquipmentType $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'avg_cost_per_hour' => $e->avg_cost_per_hour,
                'daily_rate' => $e->daily_rate,
                'flat_price' => $e->flat_price,
                'included_days' => $e->included_days,
                'included_tons' => $e->included_tons,
                'price_per_additional_ton' => $e->price_per_additional_ton,
                'price_per_additional_day' => $e->price_per_additional_day,
                'customer_instructions' => $e->customer_instructions,
            ]);

        return response()->json(['equipment' => $equipment]);
    }
}
