<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'active' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    /** Request-memoized id => username map, for resolving assignee-id arrays to names. */
    public static function nameMap(): array
    {
        static $map = null;

        return $map ??= self::pluck('username', 'id')->all();
    }

    /** Comma-joined usernames for a list of admin ids (order preserved, unknowns dropped). */
    public static function namesFor(array $ids): string
    {
        $map = self::nameMap();

        return implode(', ', array_values(array_filter(array_map(fn ($id) => $map[$id] ?? null, $ids))));
    }
}
