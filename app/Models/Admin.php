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
        ];
    }
}
