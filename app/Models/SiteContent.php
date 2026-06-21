<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Editable site content (marketing copy + serving areas).
 *
 * Field definitions and their defaults live in config/site_content.php. A row
 * here is only created once an admin overrides a default, so the public site
 * renders the config defaults until then.
 */
class SiteContent extends Model
{
    protected $table = 'site_content';

    protected $guarded = [];

    /** Per-request cache of stored overrides (key => raw value). */
    protected static ?array $overridesCache = null;

    /** All editable field definitions. */
    public static function fields(): array
    {
        return config('site_content', []);
    }

    protected static function overrides(): array
    {
        if (static::$overridesCache === null) {
            static::$overridesCache = static::query()->pluck('value', 'key')->all();
        }

        return static::$overridesCache;
    }

    /** Resolved value for a key: the stored override, else the config default. */
    public static function value(string $key): mixed
    {
        $field = static::fields()[$key] ?? null;
        if ($field === null) {
            return null;
        }

        $stored = static::overrides()[$key] ?? null;

        if (in_array($field['type'] ?? 'html', ['list', 'cards'], true)) {
            if ($stored === null) {
                return $field['default'] ?? [];
            }
            $decoded = json_decode($stored, true);

            return is_array($decoded) ? $decoded : ($field['default'] ?? []);
        }

        if (($field['type'] ?? 'html') === 'boolean') {
            if ($stored === null) {
                return (bool) ($field['default'] ?? false);
            }

            return $stored === '1' || $stored === 'true';
        }

        return $stored ?? ($field['default'] ?? '');
    }

    /** Boolean toggle content (e.g. show the quote form) for a key. */
    public static function bool(string $key): bool
    {
        return (bool) static::value($key);
    }

    /** HTML content (already sanitized on save) for a key. */
    public static function html(string $key): string
    {
        return (string) static::value($key);
    }

    /** List content (e.g. serving areas) for a key. */
    public static function list(string $key): array
    {
        $value = static::value($key);

        return is_array($value) ? $value : [];
    }

    /** Card list (e.g. home service cards) for a key. */
    public static function cards(string $key): array
    {
        $value = static::value($key);

        return is_array($value) ? array_values($value) : [];
    }

    public static function forgetCache(): void
    {
        static::$overridesCache = null;
    }
}
