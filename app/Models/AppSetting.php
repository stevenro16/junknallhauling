<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global, admin-editable app settings as simple key/value rows. Use the static
 * helpers rather than querying directly. Values are stored as strings ('1'/'0'
 * for booleans).
 */
class AppSetting extends Model
{
    protected $guarded = [];

    /** Per-request cache of all settings (key => raw string value). */
    protected static ?array $cache = null;

    protected static function values(): array
    {
        return static::$cache ??= static::query()->pluck('value', 'key')->all();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = static::get($key);

        return $v === null ? $default : ($v === '1' || $v === 'true');
    }

    public static function set(string $key, string|bool $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : $value;
        static::updateOrCreate(['key' => $key], ['value' => $stored]);
        static::$cache = null;
    }
}
