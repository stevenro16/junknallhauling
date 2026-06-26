<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentType extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'avg_cost_per_hour' => 'float',
            'daily_rate' => 'float',
            'flat_price' => 'float',
            'included_days' => 'integer',
            'included_tons' => 'float',
            'price_per_additional_ton' => 'float',
            'price_per_additional_day' => 'float',
            'active' => 'boolean',
            'customer_visible' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** True when this item uses flat-rate (dumpster/trailer) pricing rather than hourly/daily. */
    public function isFlatRate(): bool
    {
        return $this->flat_price !== null && (float) $this->flat_price > 0;
    }

    /**
     * Upfront estimate for a flat-rate rental of $days days: base price plus any
     * extra-day charges. Tonnage overage is excluded (only known at disposal).
     * Returns null for non-flat-rate items.
     */
    public function flatRateEstimate(int $days): ?float
    {
        if (! $this->isFlatRate()) {
            return null;
        }

        $extraDays = max(0, $days - (int) ($this->included_days ?? 0));

        return round((float) $this->flat_price + $extraDays * (float) ($this->price_per_additional_day ?? 0), 2);
    }

    /** Agreement the customer must sign when this equipment is on a quote (or null). */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }
}
