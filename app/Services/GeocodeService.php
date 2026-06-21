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
                    $line1 = trim(($a['house_number'] ?? '').' '.($a['road'] ?? ''));
                    $city = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['hamlet'] ?? $a['municipality'] ?? '';
                    $region = trim(($a['state'] ?? '').' '.($a['postcode'] ?? ''));
                    $parts = array_filter([$line1, $city, $region], fn ($p) => $p !== '');
                    $value = $parts
                        ? implode(', ', $parts)
                        : trim((string) preg_replace('/,?\s*United States$/', '', (string) ($d['display_name'] ?? '')));

                    return $value === '' ? null : ['label' => $value, 'value' => $value];
                }, $data)));
            } catch (\Throwable) {
                return [];
            }
        });
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
