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
            'photos' => 'array',
            'arrival_photos' => 'array',
            'departure_photos' => 'array',
        ];
    }

    /** Compose the full address string from its parts, e.g. "123 Main St, Yucaipa, CA 92399". */
    public static function composeAddress(?string $street, ?string $city, ?string $state, ?string $zip): string
    {
        $tail = trim(implode(' ', array_filter([trim((string) $state), trim((string) $zip)])));
        $parts = array_filter([trim((string) $street), trim((string) $city), $tail], fn ($p) => $p !== '');

        return implode(', ', $parts);
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

    /**
     * The agreement template attached to this inquiry's equipment or service item
     * (equipment takes precedence), or null if none requires one.
     */
    public function agreementTemplate(): ?Agreement
    {
        if ($this->equipment_type) {
            $equipment = EquipmentType::where('name', $this->equipment_type)->first();
            if ($equipment?->agreement_id) {
                return $equipment->agreement;
            }
        }

        if ($this->service_type) {
            $service = ServiceCatalog::where('key', $this->service_type)->first();
            if ($service?->agreement_id) {
                return $service->agreement;
            }
        }

        return null;
    }

    /**
     * Which channel a customer notification for this inquiry should go out on,
     * honoring BOTH the global site toggles and the customer's preferred contact
     * method. Returns 'email', 'sms', or null when nothing should be sent — i.e.
     * the customer's preferred channel is switched off site-wide, or we have no
     * address/number for it. Notifications are never sent on a non-preferred
     * channel. (Sending isn't wired up yet; this is the gate it will consult.)
     */
    public function customerNotificationChannel(): ?string
    {
        $channel = $this->preferred_contact_method === 'email' ? 'email' : 'sms';

        $enabled = $channel === 'email'
            ? AppSetting::bool('customer_notify_email')
            : AppSetting::bool('customer_notify_sms');

        if (! $enabled) {
            return null;
        }
        if ($channel === 'email' && empty($this->email)) {
            return null;
        }
        if ($channel === 'sms' && empty($this->phone)) {
            return null;
        }

        return $channel;
    }

    /** True when this inquiry's item requires an agreement that isn't signed yet. */
    public function needsAgreement(): bool
    {
        return $this->agreementTemplate() !== null
            && ! $this->rentalAgreements()->whereNotNull('signed_at')->exists();
    }

    /**
     * Reuse a still-usable unsigned agreement link, or mint a fresh one tied to the
     * inquiry's attached agreement template. Returns null if no item requires one.
     */
    public function ensureAgreementLink(): ?RentalAgreement
    {
        $template = $this->agreementTemplate();
        if (! $template) {
            return null;
        }

        $existing = $this->rentalAgreements()
            ->whereNull('signed_at')->whereNull('cancelled_at')->orderByDesc('created_at')
            ->get()->first(fn (RentalAgreement $a) => $a->isUsable());
        if ($existing) {
            return $existing;
        }

        $link = $this->rentalAgreements()->create([
            'token' => (string) Str::uuid(),
            'agreement_id' => $template->id,
            'form_data' => [],
        ]);
        $this->logAudit('rental_agreement_sent');

        return $link;
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
