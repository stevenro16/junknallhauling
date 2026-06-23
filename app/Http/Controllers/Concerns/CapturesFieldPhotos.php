<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;
use Illuminate\Http\Request;

trait CapturesFieldPhotos
{
    private const PHOTO_MAX = 12;   // per bucket, a safety cap

    /** Append uploaded photo(s) to the arrival/departure bucket (stored as base64 data URLs). */
    protected function storeFieldPhotos(Inquiry $inquiry, string $which, Request $request): void
    {
        $column = $which === 'departure' ? 'departure_photos' : 'arrival_photos';
        $photos = $inquiry->{$column} ?? [];

        foreach ((array) $request->file('photos', []) as $file) {
            if (count($photos) >= self::PHOTO_MAX) {
                break;
            }
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $mime = (string) $file->getMimeType();
            if (! str_starts_with($mime, 'image/') || $file->getSize() > 5 * 1024 * 1024) {
                continue;
            }
            $photos[] = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
        }

        $inquiry->update([$column => $photos]);
    }

    /** Remove one photo (by index) from the arrival/departure bucket. */
    protected function removeFieldPhoto(Inquiry $inquiry, string $which, int $index): void
    {
        $column = $which === 'departure' ? 'departure_photos' : 'arrival_photos';
        $photos = $inquiry->{$column} ?? [];
        if (isset($photos[$index])) {
            array_splice($photos, $index, 1);
            $inquiry->update([$column => array_values($photos)]);
        }
    }
}
