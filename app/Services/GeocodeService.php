<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodeService
{
    /**
     * Geocode an address via Nominatim (OpenStreetMap). Mirrors lib/geocode.ts.
     * Returns ['lat' => float, 'lng' => float] or null.
     */
    public function geocode(string $address): ?array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => 'JunkNAllHauling/1.0 (junknallhauling@gmail.com)'])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'            => $address,
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => 'us',
                ]);

            if (! $res->successful()) {
                return null;
            }

            $data = $res->json();
            if (! is_array($data) || empty($data)) {
                return null;
            }

            return ['lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon']];
        } catch (\Throwable) {
            return null;
        }
    }
}
