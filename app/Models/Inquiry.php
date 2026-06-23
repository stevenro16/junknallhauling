<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Inquiry extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'initial_estimated_quote' => 'float',
            'quoted_price' => 'float',
            'equipment_rental_duration' => 'integer',
            'expected_duration_minutes' => 'integer',
            'arrived_at' => 'datetime',
            'departed_at' => 'datetime',
            'service_signed_at' => 'datetime',
            'quote_verified' => 'boolean',
            'address_verified' => 'boolean',
            'date_time_verified' => 'boolean',
            'contact_verified' => 'boolean',
            'assigned_employee_ids' => 'array',
            'pickup_assigned_employee_ids' => 'array',
            'signatures' => 'array',
        ];
    }

    /** Assignee ids for a visit/pickup — the JSON array, falling back to the legacy single column. */
    public function assigneeIds(string $type): array
    {
        $arr = $type === 'pickup' ? $this->pickup_assigned_employee_ids : $this->assigned_employee_ids;
        if (is_array($arr) && count($arr)) {
            return array_values($arr);
        }
        $single = $type === 'pickup' ? $this->pickup_assigned_employee_id : $this->assigned_employee_id;

        return $single ? [$single] : [];
    }

    protected static function booted(): void
    {
        // Generate a HAUL-XXXX reference on creation (mirrors generateRef()).
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->ref)) {
                $inquiry->ref = self::generateRef();
            }
        });
    }

    public static function generateRef(): string
    {
        $short = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 4));

        return "HAUL-{$short}";
    }

    // --- Relationships ----------------------------------------------------

    public function statusHistory(): HasMany
    {
        return $this->hasMany(InquiryStatusHistory::class, 'inquiry_id');
    }

    public function rentalAgreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class, 'inquiry_id');
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class, 'inquiry_id');
    }

    public function detailRequests(): HasMany
    {
        return $this->hasMany(QuoteDetailRequest::class, 'inquiry_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(InquiryComment::class, 'inquiry_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_employee_id');
    }

    public function pickupAssignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'pickup_assigned_employee_id');
    }

    /** Status-history rows that represent verification events (public-visible subset). */
    public function verificationHistory(): HasMany
    {
        return $this->statusHistory()
            ->where('new_status', 'like', '%verified%')
            ->orderByDesc('changed_at');
    }

    // --- Audit helpers (mirror logStatusChange / logAuditEvent) ------------

    public function logStatusChange(?string $oldStatus, string $newStatus, string $changedBy = 'admin'): void
    {
        $this->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'changed_at' => now()->toISOString(),
        ]);
    }

    public function logAudit(string $action, string $changedBy = 'admin'): void
    {
        $this->statusHistory()->create([
            'old_status' => null,
            'new_status' => $action,
            'changed_by' => $changedBy,
            'changed_at' => now()->toISOString(),
        ]);
    }
}
