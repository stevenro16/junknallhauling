<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// One-time data backfill: the home service cards predate the optional
// per-card "bottom link" (caption + URL) feature. Restore their original
// "Request a Quote → /contact" call-to-action so the public home page keeps
// the link it has always shown. Idempotent — only fills cards missing it.
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('site_content')->where('key', 'home_service_cards')->first();
        if (! $row) {
            return; // no override saved; the config default already carries the link
        }

        $cards = json_decode($row->value, true);
        if (! is_array($cards)) {
            return;
        }

        $changed = false;
        foreach ($cards as &$card) {
            if (! is_array($card)) {
                continue;
            }
            if (($card['link_label'] ?? '') === '' && ($card['link_url'] ?? '') === '') {
                $card['link_label'] = 'Request a Quote';
                $card['link_url'] = '/contact';
                $changed = true;
            }
        }
        unset($card);

        if ($changed) {
            DB::table('site_content')->where('key', 'home_service_cards')
                ->update(['value' => json_encode($cards)]);
        }
    }

    public function down(): void
    {
        // Data backfill only — nothing to reverse.
    }
};
