<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RouteEstimator
{
    /**
     * Driving time + distance between two points via the public OSRM demo server
     * (free, no API key). Returns ['duration_minutes' => int, 'distance_miles' =>
     * float] or null on failure. Note OSRM expects lon,lat order.
     */
    public function estimate(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        try {
            $res = Http::timeout(8)->get(
                "https://router.project-osrm.org/route/v1/driving/{$fromLng},{$fromLat};{$toLng},{$toLat}",
                ['overview' => 'false']
            );

            $route = $res->successful() ? $res->json('routes.0') : null;
            if (! $route || ! isset($route['duration'], $route['distance'])) {
                return null;
            }

            return [
                'duration_minutes' => (int) max(1, round($route['duration'] / 60)),
                'distance_miles' => round($route['distance'] / 1609.344, 1),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
