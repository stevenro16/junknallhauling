<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['services' => ServiceCatalog::orderByDesc('active')->orderBy('key')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $key = (string) $request->input('key');
        $label = trim((string) $request->input('label'));
        if ($key === '' || $label === '') {
            return response()->json(['error' => 'Key and label are required'], 400);
        }
        if (ServiceCatalog::where('key', $key)->exists()) {
            return response()->json(['error' => 'A service with that key already exists'], 409);
        }

        $service = ServiceCatalog::create([
            'key' => $key,
            'label' => $label,
            'default_price' => $request->input('default_price') !== null ? (float) $request->input('default_price') : null,
            'default_duration_minutes' => $request->input('default_duration_minutes') !== null ? (int) $request->input('default_duration_minutes') : 120,
            'active' => true,
            'customer_visible' => $request->has('customer_visible') ? (bool) $request->input('customer_visible') : true,
        ]);

        return response()->json(['service' => $service], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $service = ServiceCatalog::find($id);
        if (! $service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $updates = [];
        if ($request->has('label')) {
            $updates['label'] = trim((string) $request->input('label'));
        }
        if ($request->has('default_price')) {
            $updates['default_price'] = $request->input('default_price') !== null && $request->input('default_price') !== '' ? (float) $request->input('default_price') : null;
        }
        if ($request->has('default_duration_minutes')) {
            $updates['default_duration_minutes'] = (int) $request->input('default_duration_minutes');
        }
        if ($request->has('active')) {
            $updates['active'] = (bool) $request->input('active');
        }
        if ($request->has('customer_visible')) {
            $updates['customer_visible'] = (bool) $request->input('customer_visible');
        }

        $service->update($updates);

        return response()->json(['service' => $service->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = ServiceCatalog::find($id);
        if (! $service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $service->delete(); // permanent delete

        return response()->json(['success' => true]);
    }
}
