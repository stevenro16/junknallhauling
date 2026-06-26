<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['equipment' => EquipmentType::orderByDesc('active')->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return response()->json(['error' => 'Name is required'], 400);
        }
        if (EquipmentType::where('name', $name)->exists()) {
            return response()->json(['error' => 'An equipment item with that name already exists'], 409);
        }

        $equipment = EquipmentType::create([
            'name' => $name,
            'avg_cost_per_hour' => $this->num($request->input('avg_cost_per_hour')),
            'daily_rate' => $this->num($request->input('daily_rate')),
            'flat_price' => $this->num($request->input('flat_price')),
            'included_days' => $this->intOrNull($request->input('included_days')),
            'included_tons' => $this->num($request->input('included_tons')),
            'price_per_additional_ton' => $this->num($request->input('price_per_additional_ton')),
            'price_per_additional_day' => $this->num($request->input('price_per_additional_day')),
            'active' => true,
            'customer_visible' => $request->has('customer_visible') ? (bool) $request->input('customer_visible') : true,
            'customer_instructions' => trim((string) $request->input('customer_instructions')) ?: null,
            'agreement_id' => $request->input('agreement_id') ?: null,
        ]);

        return response()->json(['equipment' => $equipment], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $equipment = EquipmentType::find($id);
        if (! $equipment) {
            return response()->json(['error' => 'Equipment item not found'], 404);
        }

        $updates = [];
        if ($request->has('name')) {
            $updates['name'] = trim((string) $request->input('name'));
        }
        if ($request->has('avg_cost_per_hour')) {
            $updates['avg_cost_per_hour'] = $this->num($request->input('avg_cost_per_hour'));
        }
        if ($request->has('daily_rate')) {
            $updates['daily_rate'] = $this->num($request->input('daily_rate'));
        }
        if ($request->has('flat_price')) {
            $updates['flat_price'] = $this->num($request->input('flat_price'));
        }
        if ($request->has('included_days')) {
            $updates['included_days'] = $this->intOrNull($request->input('included_days'));
        }
        if ($request->has('included_tons')) {
            $updates['included_tons'] = $this->num($request->input('included_tons'));
        }
        if ($request->has('price_per_additional_ton')) {
            $updates['price_per_additional_ton'] = $this->num($request->input('price_per_additional_ton'));
        }
        if ($request->has('price_per_additional_day')) {
            $updates['price_per_additional_day'] = $this->num($request->input('price_per_additional_day'));
        }
        if ($request->has('active')) {
            $updates['active'] = (bool) $request->input('active');
        }
        if ($request->has('customer_visible')) {
            $updates['customer_visible'] = (bool) $request->input('customer_visible');
        }
        if ($request->has('customer_instructions')) {
            $updates['customer_instructions'] = trim((string) $request->input('customer_instructions')) ?: null;
        }
        if ($request->has('agreement_id')) {
            $updates['agreement_id'] = $request->input('agreement_id') ?: null;
        }

        $equipment->update($updates);

        return response()->json(['equipment' => $equipment->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $equipment = EquipmentType::find($id);
        if (! $equipment) {
            return response()->json(['error' => 'Equipment item not found'], 404);
        }

        $equipment->delete(); // permanent delete (matches the service catalog)

        return response()->json(['success' => true]);
    }

    /** Parse an optional numeric input to float, or null when blank. */
    private function num(mixed $value): ?float
    {
        return ($value !== null && $value !== '') ? (float) $value : null;
    }

    /** Parse an optional integer input, or null when blank. */
    private function intOrNull(mixed $value): ?int
    {
        return ($value !== null && $value !== '') ? (int) $value : null;
    }
}
