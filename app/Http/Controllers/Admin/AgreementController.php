<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\EquipmentType;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['agreements' => Agreement::orderByDesc('active')->orderBy('title')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $title = trim((string) $request->input('title'));
        if ($title === '') {
            return response()->json(['error' => 'Title is required'], 400);
        }

        $agreement = Agreement::create([
            'title' => $title,
            'acknowledgments' => $this->parseAcknowledgments($request->input('acknowledgments')),
            'instructions' => trim((string) $request->input('instructions')) ?: null,
            'active' => true,
        ]);

        return response()->json(['agreement' => $agreement], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $agreement = Agreement::find($id);
        if (! $agreement) {
            return response()->json(['error' => 'Agreement not found'], 404);
        }

        $updates = [];
        if ($request->has('title')) {
            $updates['title'] = trim((string) $request->input('title'));
        }
        if ($request->has('acknowledgments')) {
            $updates['acknowledgments'] = $this->parseAcknowledgments($request->input('acknowledgments'));
        }
        if ($request->has('instructions')) {
            $updates['instructions'] = trim((string) $request->input('instructions')) ?: null;
        }
        if ($request->has('active')) {
            $updates['active'] = (bool) $request->input('active');
        }

        $agreement->update($updates);

        return response()->json(['agreement' => $agreement->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $agreement = Agreement::find($id);
        if (! $agreement) {
            return response()->json(['error' => 'Agreement not found'], 404);
        }

        // Detach from any catalog items so nothing points at a missing agreement.
        ServiceCatalog::where('agreement_id', $id)->update(['agreement_id' => null]);
        EquipmentType::where('agreement_id', $id)->update(['agreement_id' => null]);
        $agreement->delete();

        return response()->json(['success' => true]);
    }

    /** Accept acknowledgments as an array or a newline-separated string → clean array. */
    private function parseAcknowledgments($input): array
    {
        $items = is_array($input) ? $input : preg_split('/\r\n|\r|\n/', (string) $input);

        return array_values(array_filter(
            array_map(fn ($s) => trim((string) $s), $items),
            fn ($s) => $s !== '',
        ));
    }
}
