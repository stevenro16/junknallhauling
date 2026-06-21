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
            ]);

        return response()->json(['equipment' => $equipment]);
    }
}
