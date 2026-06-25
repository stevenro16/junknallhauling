<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detailed Report — {{ $inquiry->ref }} · {{ config('business.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.jpg') }}">
    @vite(['resources/css/app.css'])
    <style>
        body { background: #e5e7eb; }
        .sheet { background: #fff; }
        .kv { display: grid; grid-template-columns: 9rem 1fr; gap: 0.25rem 1rem; }
        .kv dt { color: #6b7280; }
        .kv dd { color: #111827; font-weight: 500; }
        @media print {
            @page { margin: 0.6in; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { box-shadow: none !important; margin: 0 !important; max-width: none !important; padding: 0 !important; }
            .avoid-break { break-inside: avoid; }
            .page-break { break-before: page; }
        }
    </style>
</head>
<body class="text-gray-900">
@php
    use Illuminate\Support\Carbon;
    $statusLabels = config('business.status_labels');
    $statusLabel = fn ($s) => $statusLabels[$s] ?? ucwords(str_replace('_', ' ', (string) $s));
    $dt = fn ($v, $fmt = 'D, M j, Y · g:i A') => $v ? Carbon::parse($v)->format($fmt) : '—';
    $isEquipment = $inquiry->service_type === 'equipment' || $inquiry->equipment_type;
    $jobLabel = $inquiry->equipment_type ?: ucwords(str_replace('-', ' ', (string) $inquiry->service_type));
    $rental = ($inquiry->equipment_rental_duration && $inquiry->equipment_rental_unit)
        ? $inquiry->equipment_rental_duration.' '.$inquiry->equipment_rental_unit : null;
    // Gather every attachment for the end of the report. Images are referenced by
    // URL (admin.job-image), never inlined as base64 — a photo-heavy report inlined
    // would exceed the host WAF's response-body limit and get rejected as a 404.
    $attachments = [];
    if ($inquiry->photo_base64) {
        $attachments[] = ['label' => 'Customer photo (from quote request)', 'src' => route('admin.job-image', [$inquiry->id, 'legacy', 0])];
    }
    foreach (($inquiry->photos ?? []) as $i => $p) { $attachments[] = ['label' => 'Customer-submitted photo '.($i + 1), 'src' => route('admin.job-image', [$inquiry->id, 'photos', $i])]; }
    foreach (($inquiry->arrival_photos ?? []) as $i => $p) { $attachments[] = ['label' => 'Arrival photo '.($i + 1), 'src' => route('admin.job-image', [$inquiry->id, 'arrival', $i])]; }
    foreach (($inquiry->departure_photos ?? []) as $i => $p) { $attachments[] = ['label' => 'Departure photo '.($i + 1), 'src' => route('admin.job-image', [$inquiry->id, 'departure', $i])]; }
@endphp

    {{-- Print toolbar (hidden when printing) --}}
    <div class="no-print sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
        <a href="{{ route('admin.inquiries.show', $inquiry->id) }}" class="text-sm text-amber-600 hover:text-amber-700">&larr; Back to quote</a>
        <button onclick="window.print()" class="btn-primary py-2 px-5 text-sm">Print / Save as PDF</button>
    </div>

    <div class="sheet max-w-3xl mx-auto my-6 p-8 shadow">
        {{-- Header --}}
        <div class="flex items-start justify-between border-b-2 border-gray-800 pb-4 mb-6">
            <div>
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" class="h-10 w-auto mb-1">
                <div class="text-sm font-bold">{{ config('business.name') }}</div>
                <div class="text-xs text-gray-500">{{ config('business.phone') }} · {{ config('business.email') }}</div>
            </div>
            <div class="text-right">
                <div class="text-lg font-black tracking-widest font-mono text-gray-800">{{ $inquiry->ref }}</div>
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border border-gray-300 bg-gray-50 mt-1">{{ $statusLabel($inquiry->status) }}</div>
                <div class="text-[10px] text-gray-400 mt-1">Generated {{ now()->format('D, M j, Y g:i A') }}</div>
            </div>
        </div>

        <h1 class="text-xl font-black mb-6">Detailed Quote Report</h1>

        {{-- Customer --}}
        <section class="avoid-break mb-6">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Customer</h2>
            <dl class="kv text-sm">
                <dt>Name</dt><dd>{{ $inquiry->name ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $inquiry->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $inquiry->email ?: '—' }}</dd>
                <dt>Address</dt><dd>{{ $inquiry->address ?: '—' }}</dd>
                <dt>Preferred</dt><dd>{{ trim(($inquiry->preferred_day ?: '').' '.($inquiry->preferred_time ?: '')) ?: 'Any' }}</dd>
                <dt>Contact via</dt><dd class="capitalize">{{ $inquiry->preferred_contact_method ?: 'phone' }}</dd>
                <dt>Urgency</dt><dd class="capitalize">{{ $inquiry->urgency ?: 'routine' }}</dd>
            </dl>
        </section>

        {{-- Job --}}
        <section class="avoid-break mb-6">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Job</h2>
            <dl class="kv text-sm">
                <dt>{{ $isEquipment ? 'Equipment' : 'Service' }}</dt><dd>{{ $jobLabel ?: '—' }}@if($rental) <span class="text-gray-400">· {{ $rental }} rental</span>@endif</dd>
                <dt>Quoted Price</dt><dd class="text-base font-bold">{{ $inquiry->quoted_price !== null ? '$'.number_format((float) $inquiry->quoted_price, 2) : '—' }}</dd>
                @if($inquiry->description)<dt>Description</dt><dd class="whitespace-pre-wrap font-normal text-gray-700">{{ $inquiry->description }}</dd>@endif
                @if($inquiry->admin_notes)<dt>Service Notes</dt><dd class="whitespace-pre-wrap font-normal text-gray-700">{{ $inquiry->admin_notes }}</dd>@endif
            </dl>
        </section>

        {{-- Visit & pickup --}}
        <section class="avoid-break mb-6">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Visit</h2>
            <dl class="kv text-sm">
                <dt>Date &amp; Time</dt><dd>{{ $dt($inquiry->confirmed_date_time) }}</dd>
                <dt>Duration</dt><dd>{{ $inquiry->expected_duration_minutes ? round($inquiry->expected_duration_minutes / 60, 2).' hrs' : '—' }}</dd>
                <dt>Assigned to</dt><dd>{{ $visitAssignees ?: 'Unassigned' }}</dd>
                @if($isEquipment)
                    <dt>Pickup</dt><dd>{{ $dt($inquiry->pickup_date_time) }}</dd>
                    <dt>Pickup crew</dt><dd>{{ $pickupAssignees ?: 'Unassigned' }}</dd>
                @endif
                <dt>Arrived</dt><dd>{{ $inquiry->arrived_at ? $inquiry->arrived_at->format('D, M j · g:i A') : '—' }}</dd>
                <dt>Departed</dt><dd>{{ $inquiry->departed_at ? $inquiry->departed_at->format('D, M j · g:i A') : '—' }}</dd>
            </dl>
        </section>

        {{-- Payment --}}
        <section class="avoid-break mb-6">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Payment</h2>
            <dl class="kv text-sm">
                <dt>Method</dt><dd>{{ $inquiry->payment_method ?: 'Not recorded' }}</dd>
                <dt>Paid on</dt><dd>{{ $inquiry->payment_date ?: '—' }}</dd>
                @foreach($paymentLinks as $l)
                    <dt>Payment link</dt>
                    <dd>${{ number_format((float) $l->amount, 2) }} · {{ $l->paid_at ? 'Paid '.Carbon::parse($l->paid_at)->format('M j, Y') : ($l->cancelled_at ? 'Cancelled' : 'Awaiting payment') }}</dd>
                @endforeach
            </dl>
        </section>

        {{-- Customer confirmation (request-details submissions) --}}
        @foreach($detailRequests as $d)
            <section class="avoid-break mb-6">
                <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Customer Confirmation</h2>
                <p class="text-sm text-gray-700">
                    Confirmed by <span class="font-semibold">{{ $d->form_data['signed_name'] ?? $inquiry->name }}</span>
                    on {{ $dt($d->signed_at) }}@if($d->ip_address) · IP {{ $d->ip_address }}@endif.
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if(($d->form_data['confirm_datetime'] ?? false))✓ Confirmed the scheduled date &amp; time. @endif
                    @if(($d->form_data['confirm_amount'] ?? false))✓ Confirmed the quoted amount.@endif
                </p>
                @if($d->signature_base64)
                    <img src="{{ route('admin.doc-image', ['detail', $d->id]) }}" alt="Customer signature" class="mt-2 max-h-24 border border-gray-200 rounded bg-white">
                @endif
            </section>
        @endforeach

        {{-- Field-action signatures --}}
        @if(! empty($inquiry->signatures))
            <section class="avoid-break mb-6">
                <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Field Signatures</h2>
                <div class="space-y-3">
                    @foreach($inquiry->signatures as $action => $sig)
                        <div class="avoid-break">
                            <div class="text-sm font-semibold">{{ ucwords(str_replace('_', ' ', $action)) }}
                                <span class="text-gray-400 font-normal">· {{ $dt($sig['signed_at'] ?? null) }}</span></div>
                            @if(! empty($sig['signature']))<img src="{{ route('admin.job-image', [$inquiry->id, 'signature', $loop->index]) }}" alt="{{ $action }} signature" class="mt-1 max-h-24 border border-gray-200 rounded bg-white">@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Signed rental agreements --}}
        @foreach($agreements as $a)
            <section class="avoid-break mb-6">
                <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Signed Rental Agreement</h2>
                <p class="text-sm text-gray-700">Signed on {{ $dt($a->signed_at) }}@if($a->ip_address) · IP {{ $a->ip_address }}@endif.</p>
                @if($a->signature_base64)<img src="{{ route('admin.doc-image', ['agreement', $a->id]) }}" alt="Agreement signature" class="mt-2 max-h-24 border border-gray-200 rounded bg-white">@endif
            </section>
        @endforeach

        {{-- Notes --}}
        @if($comments->isNotEmpty())
            <section class="avoid-break mb-6">
                <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Notes &amp; Comments</h2>
                <div class="space-y-2">
                    @foreach($comments as $c)
                        <div class="text-sm avoid-break">
                            <span class="font-semibold">{{ $c->author_name ?: 'Staff' }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $c->customer_visible ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500' }}">{{ $c->customer_visible ? 'Customer-visible' : 'Internal' }}</span>
                            <span class="text-gray-400 text-xs">· {{ $dt($c->created_at) }}</span>
                            <div class="text-gray-700 whitespace-pre-wrap">{{ $c->body }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Audit trail --}}
        <section class="avoid-break mb-2">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-2">Audit Trail</h2>
            @if($history->isEmpty())
                <p class="text-sm text-gray-400">No history recorded.</p>
            @else
                <table class="w-full text-xs">
                    <tbody>
                        @foreach($history as $h)
                            <tr class="border-b border-gray-100">
                                <td class="py-1 pr-3 text-gray-500 whitespace-nowrap align-top">{{ $dt($h->changed_at, 'M j, Y g:i A') }}</td>
                                <td class="py-1 pr-3 align-top">
                                    @if($h->old_status)
                                        {{ $statusLabel($h->old_status) }} <span class="text-gray-400">&rarr;</span> <span class="font-semibold">{{ $statusLabel($h->new_status) }}</span>
                                    @else
                                        <span class="font-semibold">{{ $statusLabel($h->new_status) }}</span>
                                    @endif
                                </td>
                                <td class="py-1 text-gray-400 text-right whitespace-nowrap align-top">by {{ $h->changed_by }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- Attachments (large images, at the end) --}}
        @if(count($attachments))
            <section class="page-break pt-6">
                <h2 class="text-xs uppercase tracking-widest text-gray-400 border-b border-gray-200 pb-1 mb-3">Attachments ({{ count($attachments) }})</h2>
                <div class="space-y-5">
                    @foreach($attachments as $att)
                        <div class="avoid-break">
                            <div class="text-xs text-gray-500 mb-1">{{ $att['label'] }}</div>
                            <img src="{{ $att['src'] }}" alt="{{ $att['label'] }}" class="w-full max-h-[8in] object-contain border border-gray-200 rounded">
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <p class="text-center text-[10px] text-gray-400 mt-8 pt-4 border-t border-gray-200">{{ config('business.name') }} · Detailed Quote Report for {{ $inquiry->ref }}</p>
    </div>
</body>
</html>
