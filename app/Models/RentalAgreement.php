<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAgreement extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'content_snapshot' => 'array',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    /** The agreement template this signing record was created from. */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    /**
     * The terms to display: the frozen snapshot captured at signing if present,
     * otherwise the current template content (for unsigned/preview).
     */
    public function effectiveContent(): array
    {
        if (! empty($this->content_snapshot)) {
            return $this->content_snapshot;
        }

        if ($this->agreement) {
            return $this->agreement->snapshot();
        }

        return ['title' => 'Agreement', 'acknowledgments' => [], 'instructions' => null];
    }

    /** True when the link can still be opened/signed by the customer. */
    public function isUsable(): bool
    {
        if ($this->signed_at || $this->cancelled_at) {
            return false;
        }

        if ($this->expires_at && now()->toISOString() > $this->expires_at) {
            return false;
        }

        return true;
    }

    public function isSigned(): bool
    {
        return ! empty($this->signed_at);
    }
}
