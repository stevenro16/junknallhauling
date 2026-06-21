<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    use HasUuids;

    protected $table = 'service_catalog';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_price' => 'float',
            'default_duration_minutes' => 'integer',
            'active' => 'boolean',
            'customer_visible' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
