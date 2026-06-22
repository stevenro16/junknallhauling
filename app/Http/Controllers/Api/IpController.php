<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpController extends Controller
{
    /**
     * Returns the client's public IP as seen by the server, handling common
     * proxy/CDN headers. Mirrors the Next.js /api/ip route.
     */
    public function show(Request $request): JsonResponse
    {
        $cf = $request->header('cf-connecting-ip');
        $xRealIp = $request->header('x-real-ip');
        $xff = $request->header('x-forwarded-for');
        $xClient = $request->header('x-client-ip');
        $forwarded = $request->header('forwarded');

        $ip = 'unknown';
        if ($cf) {
            $ip = trim($cf);
        } elseif ($xRealIp) {
            $ip = trim($xRealIp);
        } elseif ($xff) {
            $ip = trim(explode(',', $xff)[0]);
        } elseif ($xClient) {
            $ip = trim($xClient);
        } elseif ($forwarded && preg_match('/for=([^;]+)/i', $forwarded, $m)) {
            $ip = trim(str_replace('"', '', $m[1]));
        } else {
            $ip = $request->ip() ?? 'unknown';
        }

        return response()->json([
            'ip' => $ip,
            'headers' => [
                'cf-connecting-ip' => $cf,
                'x-forwarded-for' => $xff,
                'x-real-ip' => $xRealIp,
                'x-client-ip' => $xClient,
            ],
        ]);
    }
}
