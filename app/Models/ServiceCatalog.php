<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** Agreement the customer must sign when this service is on a quote (or null). */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }
}
