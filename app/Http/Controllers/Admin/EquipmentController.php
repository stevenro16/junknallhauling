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
            'avg_cost_per_hour' => $request->input('avg_cost_per_hour') !== null && $request->input('avg_cost_per_hour') !== '' ? (float) $request->input('avg_cost_per_hour') : null,
            'daily_rate' => $request->input('daily_rate') !== null && $request->input('daily_rate') !== '' ? (float) $request->input('daily_rate') : null,
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
            $updates['avg_cost_per_hour'] = $request->input('avg_cost_per_hour') !== null && $request->input('avg_cost_per_hour') !== '' ? (float) $request->input('avg_cost_per_hour') : null;
        }
        if ($request->has('daily_rate')) {
            $updates['daily_rate'] = $request->input('daily_rate') !== null && $request->input('daily_rate') !== '' ? (float) $request->input('daily_rate') : null;
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
}
