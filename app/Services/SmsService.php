<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS via the Twilio REST API (no SDK — uses the bundled HTTP client).
 * When Twilio isn't configured, sending is a logged no-op that returns false and
 * never throws, so callers can fire-and-forget without risking the request.
 */
class SmsService
{
    private ?string $sid;

    private ?string $token;

    private ?string $from;

    public function __construct()
    {
        $this->sid = config('services.twilio.sid');
        $this->token = config('services.twilio.token');
        $this->from = config('services.twilio.from');
    }

    public function configured(): bool
    {
        return filled($this->sid) && filled($this->token) && filled($this->from);
    }

    /** Send a text. Returns true if Twilio accepted it. */
    public function send(?string $to, string $body): bool
    {
        $to = $this->e164($to);
        if (! $to || trim($body) === '') {
            return false;
        }

        if (! $this->configured()) {
            Log::info('SMS skipped — Twilio not configured', ['to' => $to]);

            return false;
        }

        // A Messaging Service SID (MG…) uses a different param than a plain number.
        $params = ['To' => $to, 'Body' => $body];
        if (str_starts_with((string) $this->from, 'MG')) {
            $params['MessagingServiceSid'] = $this->from;
        } else {
            $params['From'] = $this->from;
        }

        try {
            $res = Http::asForm()->withBasicAuth($this->sid, $this->token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", $params);

            if ($res->failed()) {
                Log::warning('Twilio send failed', ['to' => $to, 'status' => $res->status(), 'message' => $res->json('message')]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Twilio send threw', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** Normalize a US phone to E.164 (+1XXXXXXXXXX). Null if unusable. */
    public function e164(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, '+')) {
            return $raw;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+'.$digits;
        }

        return '+'.$digits;
    }
}
