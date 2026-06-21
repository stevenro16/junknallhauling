<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private GeocodeService $geocoder) {}

    /** GET /admin/api/address-suggest?q= — address autocomplete (OpenStreetMap). */
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json($this->geocoder->suggest($query));
    }
}
