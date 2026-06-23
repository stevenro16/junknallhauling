<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodeService
{
    /**
     * Address autocomplete suggestions via Nominatim (OpenStreetMap). Admin-only
     * use (low volume), debounced client-side and cached here to stay well within
     * Nominatim's usage policy. Returns a list of ['label' => string, 'value' =>
     * string] — value is a tidy address to drop into the field.
     */
    public function suggest(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('addr_suggest:'.md5(mb_strtolower($query)), 300, function () use ($query, $limit) {
            try {
                $res = Http::withHeaders(['User-Agent' => 'JunkNAllHauling/1.0 (junknallhauling@gmail.com)'])
                    ->timeout(6)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'limit' => $limit,
                        'countrycodes' => 'us',
                    ]);

                if (! $res->successful()) {
                    return [];
                }

                $data = $res->json();
                if (! is_array($data)) {
                    return [];
                }

                return array_values(array_filter(array_map(function ($d) {
                    $a = $d['address'] ?? [];
                    $street = trim(($a['house_number'] ?? '').' '.($a['road'] ?? ''));
                    $city = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['hamlet'] ?? $a['municipality'] ?? '';
                    $state = self::stateAbbr((string) ($a['state'] ?? ''));
                    $zip = (string) ($a['postcode'] ?? '');
                    $parts = array_filter([$street, $city, trim($state.' '.$zip)], fn ($p) => $p !== '');
                    $label = $parts
                        ? implode(', ', $parts)
                        : trim((string) preg_replace('/,?\s*United States$/', '', (string) ($d['display_name'] ?? '')));

                    // Return the structured parts so the form can fill each field.
                    return $label === '' ? null : [
                        'label' => $label,
                        'value' => $street !== '' ? $street : $label,   // Street field
                        'street' => $street,
                        'city' => (string) $city,
                        'state' => $state,
                        'zip' => $zip,
                    ];
                }, $data)));
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** Map a full US state name to its 2-letter code (pass through if already short/unknown). */
    private static function stateAbbr(string $state): string
    {
        $state = trim($state);
        if ($state === '' || mb_strlen($state) <= 2) {
            return strtoupper($state);
        }

        static $map = [
            'alabama' => 'AL', 'alaska' => 'AK', 'arizona' => 'AZ', 'arkansas' => 'AR', 'california' => 'CA',
            'colorado' => 'CO', 'connecticut' => 'CT', 'delaware' => 'DE', 'district of columbia' => 'DC',
            'florida' => 'FL', 'georgia' => 'GA', 'hawaii' => 'HI', 'idaho' => 'ID', 'illinois' => 'IL',
            'indiana' => 'IN', 'iowa' => 'IA', 'kansas' => 'KS', 'kentucky' => 'KY', 'louisiana' => 'LA',
            'maine' => 'ME', 'maryland' => 'MD', 'massachusetts' => 'MA', 'michigan' => 'MI', 'minnesota' => 'MN',
            'mississippi' => 'MS', 'missouri' => 'MO', 'montana' => 'MT', 'nebraska' => 'NE', 'nevada' => 'NV',
            'new hampshire' => 'NH', 'new jersey' => 'NJ', 'new mexico' => 'NM', 'new york' => 'NY',
            'north carolina' => 'NC', 'north dakota' => 'ND', 'ohio' => 'OH', 'oklahoma' => 'OK', 'oregon' => 'OR',
            'pennsylvania' => 'PA', 'rhode island' => 'RI', 'south carolina' => 'SC', 'south dakota' => 'SD',
            'tennessee' => 'TN', 'texas' => 'TX', 'utah' => 'UT', 'vermont' => 'VT', 'virginia' => 'VA',
            'washington' => 'WA', 'west virginia' => 'WV', 'wisconsin' => 'WI', 'wyoming' => 'WY',
        ];

        return $map[mb_strtolower($state)] ?? $state;
    }

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
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
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
