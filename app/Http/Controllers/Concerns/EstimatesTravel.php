<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;
use App\Services\GeocodeService;
use App\Services\RouteEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait EstimatesTravel
{
    /** Driving estimate from the caller's current location to an inquiry's address. */
    protected function travelEstimate(Inquiry $inquiry, Request $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        if (! $lat || ! $lng) {
            return response()->json(['error' => 'Your current location is required.'], 422);
        }

        // Destination: the inquiry's stored coords, geocoding the address as a fallback.
        $dLat = $inquiry->latitude;
        $dLng = $inquiry->longitude;
        if (($dLat === null || $dLng === null) && $inquiry->address) {
            if ($coords = app(GeocodeService::class)->geocode($inquiry->address)) {
                $dLat = $coords['lat'];
                $dLng = $coords['lng'];
            }
        }
        if ($dLat === null || $dLng === null) {
            return response()->json(['error' => 'This quote has no mappable address.'], 422);
        }

        $est = app(RouteEstimator::class)->estimate($lat, $lng, (float) $dLat, (float) $dLng);
        if (! $est) {
            return response()->json(['error' => 'Could not calculate the route right now. Try again.'], 502);
        }

        return response()->json($est);
    }
}
