@extends('layouts.admin')

@section('title', 'Rental Agreement — '.config('business.name'))

@section('admin-content')
@php
    $inq = $agreement->inquiry;
    $fd = $agreement->form_data ?? [];
    $signed = ! empty($agreement->signed_at);
    $cancelled = ! empty($agreement->cancelled_at);

    // Prefer the snapshot captured at signing (the exact terms the customer
    // agreed to); fall back to the live quote for unsigned agreements.
    $snap = (isset($fd['inquiry_snapshot']) && is_array($fd['inquiry_snapshot'])) ? $fd['inquiry_snapshot'] : null;
    $val = function (string $key, $default = '—') use ($snap, $inq) {
        $v = ($snap !== null && array_key_exists($key, $snap)) ? $snap[$key] : ($inq?->{$key} ?? null);
        return ($v === null || $v === '') ? $default : $v;
    };

    $duration = ($val('equipment_rental_duration', null) && $val('equipment_rental_unit', null))
        ? $val('equipment_rental_duration').' '.$val('equipment_rental_unit') : '—';
    $confirmed = $val('confirmed_date_time', null)
        ? \Illuminate\Support\Carbon::parse($val('confirmed_date_time'))->format('l, F j, Y g:i A') : '—';
    $quoted = $val('quoted_price', null);
@endphp

<div class="max-w-3xl mx-auto pb-10">
    {{-- Toolbar (not printed) --}}
    <div class="mb-4 flex items-center justify-between gap-3 print:hidden">
        @if($inq)
            <a href="{{ route('admin.inquiries.show', $inq->id) }}" class="text-sm text-amber-600 hover:text-amber-700">&larr; Back to quote</a>
        @else
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-amber-600 hover:text-amber-700">&larr; Back</a>
        @endif
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                {{ $signed ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($cancelled ? 'bg-gray-100 text-gray-500 border-gray-300' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                {{ $signed ? 'Signed' : ($cancelled ? 'Cancelled' : 'Awaiting signature') }}
            </span>
            <button type="button" onclick="window.print()" class="btn-primary text-xs py-1.5 px-4">
                <x-icon name="file-text" class="w-4 h-4"/> Print
            </button>
        </div>
    </div>

    {{-- Printable contract --}}
    <div class="card-light p-6 md:p-8 print:shadow-none print:border-0 print:p-0">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" class="h-12 w-auto mx-auto mb-3 hidden print:block">
            <h1 class="text-2xl font-black tracking-tight text-gray-900">Dumpster Rental Contract Agreement</h1>
            <p class="text-gray-700 mt-1 text-sm">The customer agrees to the following terms and conditions for the dumpster for services.</p>
            @if($inq)
                <p class="text-xs text-gray-500 mt-1">Quote <span class="font-mono text-amber-700">{{ $inq->ref }}</span></p>
            @endif
        </div>

        {{-- Customer information --}}
        <h2 class="font-semibold text-base text-gray-800 mb-3 border-b border-gray-200 pb-1">Customer Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-6">
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Name</div><div class="font-medium text-gray-900">{{ $val('name') }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Phone</div><div class="text-gray-900">{{ $val('phone') }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Email</div><div class="text-gray-900 break-words">{{ $val('email') }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Address</div><div class="text-gray-900">{{ $val('address') }}</div></div>
        </div>

        {{-- Rental details --}}
        <h2 class="font-semibold text-base text-gray-800 mb-3 border-b border-gray-200 pb-1">Rental Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-2">
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Equipment Needed</div><div class="text-gray-900">{{ $val('equipment_type') }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Expected Duration</div><div class="text-gray-900">{{ $duration }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Quoted Price</div><div class="font-semibold text-gray-900">{{ $quoted ? '$'.number_format((float) $quoted, 2) : '—' }}</div></div>
            <div><div class="text-xs uppercase tracking-wider text-gray-400">Pickup Address</div><div class="text-gray-900">{{ $val('address') }}</div></div>
        </div>
        <div class="text-sm mb-2"><div class="text-xs uppercase tracking-wider text-gray-400">Confirmed Date &amp; Time</div><div class="text-gray-900">{{ $confirmed }}</div></div>
        @if($val('admin_notes', null))
            <div class="text-sm mb-6"><div class="text-xs uppercase tracking-wider text-gray-400">Service Notes</div><div class="text-gray-800 whitespace-pre-wrap">{{ $val('admin_notes') }}</div></div>
        @else
            <div class="mb-6"></div>
        @endif

        {{-- Acknowledgments --}}
        <h2 class="font-semibold text-base text-gray-800 mb-3 border-b border-gray-200 pb-1">Customer Acknowledgments</h2>
        <div class="space-y-2 text-sm text-gray-800 mb-5">
            @foreach(config('agreement.acknowledgments') as $ack)
                <div class="flex items-start gap-2.5">
                    <x-icon name="{{ $signed ? 'check-circle' : 'circle' }}" class="w-4 h-4 mt-0.5 shrink-0 {{ $signed ? 'text-emerald-600' : 'text-gray-300' }}"/>
                    <span>{{ $ack }}</span>
                </div>
            @endforeach
        </div>

        <div class="text-sm text-gray-800 mb-2">
            <p class="font-medium mb-1">Prohibited Items:</p>
            <p>I understand that the following items are <strong>not allowed</strong> to be placed in the dumpster: {{ config('agreement.prohibited_items') }}</p>
        </div>
        <div class="text-sm text-gray-800 mb-6">
            <p>{{ config('agreement.tire_pricing') }}</p>
            <p class="mt-1">{{ config('agreement.tire_note') }}</p>
        </div>

        {{-- Signature block --}}
        <h2 class="font-semibold text-base text-gray-800 mb-3 border-b border-gray-200 pb-1">Signature</h2>
        @if($signed)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-4">
                <div><div class="text-xs uppercase tracking-wider text-gray-400">Signed by</div><div class="font-medium text-gray-900">{{ $fd['signed_name'] ?? $val('name') }}</div></div>
                <div><div class="text-xs uppercase tracking-wider text-gray-400">Signed on</div><div class="text-gray-900">{{ \Illuminate\Support\Carbon::parse($agreement->signed_at)->format('M j, Y g:i A') }}</div></div>
                @if(! empty($fd['pickup_date']))
                    <div><div class="text-xs uppercase tracking-wider text-gray-400">Pickup date</div><div class="text-gray-900">{{ $fd['pickup_date'] }}</div></div>
                @endif
                @if(! empty($fd['pickup_time']))
                    <div><div class="text-xs uppercase tracking-wider text-gray-400">Pickup time</div><div class="text-gray-900">{{ $fd['pickup_time'] }}</div></div>
                @endif
                @if(! empty($agreement->ip_address))
                    <div><div class="text-xs uppercase tracking-wider text-gray-400">Signed from IP</div><div class="text-gray-900 font-mono text-xs">{{ $agreement->ip_address }}</div></div>
                @endif
            </div>
            @if(! empty($fd['customer_notes']))
                <div class="text-sm mb-4"><div class="text-xs uppercase tracking-wider text-gray-400">Customer notes</div><div class="text-gray-800 whitespace-pre-wrap">{{ $fd['customer_notes'] }}</div></div>
            @endif
            @if($agreement->signature_base64)
                <img src="{{ $agreement->signature_base64 }}" alt="Customer signature"
                     class="max-w-xs w-full rounded-lg border border-gray-300 bg-white">
            @endif
            <p class="text-xs text-emerald-700 mt-3 print:text-gray-700">&check; Customer read, understood, and agreed to all terms and conditions above.</p>
        @else
            <div class="text-sm text-gray-600">
                <p class="mb-2">{{ $cancelled ? 'This agreement link was cancelled and has not been signed.' : 'This agreement has not been signed by the customer yet.' }}</p>
                @unless($cancelled)
                    <div class="flex items-center gap-2 print:hidden">
                        <input type="text" readonly value="{{ route('rental-agreement.show', $agreement->token) }}" onfocus="this.select()" class="input-light text-xs py-1.5 flex-1">
                        <a href="{{ route('rental-agreement.show', $agreement->token) }}" target="_blank" rel="noopener" class="btn-outline !px-3 !py-1.5 text-xs whitespace-nowrap">Open</a>
                    </div>
                @endunless
                <div class="mt-10 pt-2 border-t border-gray-400 w-64">
                    <div class="text-xs text-gray-500">Customer signature</div>
                </div>
            </div>
        @endif

        <p class="text-center text-xs text-gray-500 mt-8 print:mt-12">{{ config('business.name') }} &bull; {{ config('business.phone') }} &bull; Serving the Inland Empire since 2019</p>
    </div>
</div>
@endsection
