<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    public function update(Request $request)
    {
        $incoming = $request->input('content', []);
        if (! is_array($incoming)) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }

        $fields = SiteContent::fields();

        foreach ($incoming as $key => $value) {
            if (! isset($fields[$key])) {
                continue; // ignore anything not in the known field set
            }

            $type = $fields[$key]['type'] ?? 'html';

            if ($type === 'list') {
                $list = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);
                $list = array_values(array_filter(array_map('trim', $list), fn ($s) => $s !== ''));
                $stored = json_encode($list);
            } elseif ($type === 'cards') {
                $stored = json_encode($this->cleanCards($value, (int) ($fields[$key]['max'] ?? 4)));
            } else {
                $stored = $this->sanitizeHtml((string) $value);
            }

            SiteContent::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type]);
        }

        SiteContent::forgetCache();

        return response()->json(['ok' => true]);
    }

    /**
     * Normalize the service-cards payload: cap at 4, drop cards without a
     * title, clean the icon slug, and sanitize each card body.
     */
    protected function cleanCards($value, int $max = 4): array
    {
        $cards = is_array($value) ? $value : [];
        $clean = [];

        foreach (array_slice($cards, 0, $max) as $card) {
            if (! is_array($card)) {
                continue;
            }
            $title = trim(strip_tags((string) ($card['title'] ?? '')));
            if ($title === '') {
                continue;
            }
            $icon = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($card['icon'] ?? 'truck')));
            // Optional bottom call-to-action link (caption + URL). Both must be
            // present for the public site to render the link.
            $linkLabel = trim(strip_tags((string) ($card['link_label'] ?? '')));
            $linkUrl = trim((string) ($card['link_url'] ?? ''));
            if (preg_match('/^\s*javascript:/i', $linkUrl)) {
                $linkUrl = '';
            }

            $entry = [
                'icon'       => $icon !== '' ? $icon : 'truck',
                'title'      => $title,
                'subheader'  => trim(strip_tags((string) ($card['subheader'] ?? ''))),
                'body'       => $this->sanitizeHtml((string) ($card['body'] ?? '')),
                'link_label' => $linkLabel,
                'link_url'   => $linkUrl,
            ];

            // Optional uploaded image (small base64 data URL). Falls back to the
            // named icon if missing, malformed, or unreasonably large.
            $image = (string) ($card['image'] ?? '');
            if ($image !== ''
                && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $image)
                && strlen($image) <= 800_000) {
                $entry['image'] = $image;
            }

            $clean[] = $entry;
        }

        return $clean;
    }

    /**
     * Allow a safe subset of formatting tags. Admins are trusted, so this is a
     * backstop (Trix already emits clean HTML): strip everything outside the
     * allowlist, then remove any inline event handlers / javascript: URLs.
     */
    protected function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4><blockquote>');
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        return trim((string) $html);
    }
}
