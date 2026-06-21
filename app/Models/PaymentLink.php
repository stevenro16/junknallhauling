<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLink extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    /** True when the link can still be opened/paid by the customer. */
    public function isUsable(): bool
    {
        if ($this->paid_at || $this->cancelled_at) {
            return false;
        }

        if ($this->expires_at && now()->toISOString() > $this->expires_at) {
            return false;
        }

        return true;
    }

    public function isPaid(): bool
    {
        return ! empty($this->paid_at);
    }
}
