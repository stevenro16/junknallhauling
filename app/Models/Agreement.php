<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An editable agreement template (title + acknowledgment items + instructions)
 * that admins manage and attach to services and/or equipment. When a customer
 * signs, a frozen copy of this content is stored on the RentalAgreement.
 */
class Agreement extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'acknowledgments' => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceCatalog::class, 'agreement_id');
    }

    public function equipmentTypes(): HasMany
    {
        return $this->hasMany(EquipmentType::class, 'agreement_id');
    }

    /** The content shape frozen into a signed agreement's snapshot. */
    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'acknowledgments' => array_values($this->acknowledgments ?? []),
            'instructions' => $this->instructions,
        ];
    }
}
