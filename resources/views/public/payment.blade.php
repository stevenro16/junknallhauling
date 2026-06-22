@extends('layouts.public')

@section('title', 'Complete Your Payment | '.config('business.name'))

@section('content')
<div x-data="paymentForm('{{ $token }}')">
    {{-- Loading --}}
    <div x-show="loading" class="min-h-screen flex items-center justify-center bg-[#F8F7F4]">
        <x-icon name="circle" class="w-8 h-8 animate-spin text-[#EAB308]"/>
    </div>

    {{-- Error / invalid link --}}
    <div x-show="!loading && error && !data" x-cloak class="min-h-screen flex items-center justify-center bg-[#F8F7F4] p-6">
        <div class="max-w-md text-center">
            <div class="text-4xl mb-4">&#9888;&#65039;</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Link Problem</h1>
            <p class="text-gray-600" x-text="error"></p>
            <p class="text-sm text-gray-500 mt-4">Please contact {{ config('business.name') }} directly if you need a new payment link.</p>
        </div>
    </div>

    {{-- Confirming a return from Stripe Checkout --}}
    <div x-show="confirming && !paid" x-cloak class="min-h-screen bg-[#F8F7F4] flex items-center justify-center p-6">
        <div class="max-w-md text-center">
            <x-icon name="circle" class="w-10 h-10 animate-spin text-[#EAB308] mx-auto mb-4"/>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Confirming your payment…</h1>
            <p class="text-gray-600" x-text="confirmNote || 'One moment while we finish up.'"></p>
        </div>
    </div>

    {{-- Paid / success --}}
    <div x-show="paid" x-cloak class="min-h-screen bg-[#F8F7F4] flex items-center justify-center p-6">
        <div class="max-w-lg w-full bg-white rounded-2xl shadow p-8 text-center">
            <x-icon name="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"/>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 mb-2">Payment Received</h1>
            <p class="text-gray-600 mb-2">Thank you! Your payment of
                <span class="font-bold text-emerald-600">$<span x-text="money(amount)"></span></span>
                for quote <span class="font-mono text-[#EAB308]" x-text="inquiry?.ref"></span> has been received.</p>
            <p class="text-sm text-gray-500">A receipt will follow. If you have any questions, call or text us at
                <span class="font-medium" x-text="business?.phone"></span>.</p>
        </div>
    </div>

    {{-- Payment form --}}
    <div x-show="!loading && data && !paid && !confirming" x-cloak class="min-h-screen bg-[#F8F7F4] py-10 px-4">
        <div class="max-w-lg mx-auto">
            <div class="text-center mb-6">
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-gray-900">Complete Your Payment</h1>
                <p class="text-gray-700 mt-2 text-sm md:text-base" x-text="business?.name"></p>
                <p class="text-xs text-gray-500 mt-1">Quote <span class="font-mono font-medium text-[#EAB308]" x-text="inquiry?.ref"></span></p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 md:p-8">
                {{-- Amount due --}}
                <div class="text-center pb-6 border-b border-gray-100">
                    <div class="text-xs uppercase tracking-widest text-gray-400 mb-1">Amount Due</div>
                    <div class="text-5xl font-black tracking-tighter text-gray-900">$<span x-text="money(amount)"></span></div>
                </div>

                {{-- Summary --}}
                <dl class="py-5 space-y-2 text-sm border-b border-gray-100">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Name</dt>
                        <dd class="text-gray-900 font-medium text-right" x-text="inquiry?.name || '—'"></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Service</dt>
                        <dd class="text-gray-900 text-right" x-text="summaryLine()"></dd>
                    </div>
                    <div class="flex justify-between gap-4" x-show="inquiry?.confirmed_date_time">
                        <dt class="text-gray-500">Scheduled</dt>
                        <dd class="text-gray-900 text-right" x-text="scheduledLabel()"></dd>
                    </div>
                    <div class="flex justify-between gap-4" x-show="inquiry?.address">
                        <dt class="text-gray-500">Address</dt>
                        <dd class="text-gray-900 text-right" x-text="inquiry?.address"></dd>
                    </div>
                </dl>

                <p x-show="error" x-text="error" x-cloak class="text-red-500 text-sm mt-4 text-center"></p>

                <button type="button" @click="pay()" :disabled="submitting"
                        class="mt-6 w-full bg-[#EAB308] hover:bg-[#CA8A04] text-charcoal-900 font-bold py-3.5 rounded-xl transition-colors disabled:opacity-60 text-lg">
                    <span x-show="!submitting">Pay $<span x-text="money(amount)"></span></span>
                    <span x-show="submitting" x-cloak>Processing…</span>
                </button>
                <p class="text-[11px] text-gray-400 text-center mt-3">Secure payment for {{ config('business.name') }}.</p>
            </div>
        </div>
    </div>
</div>
@endsection
