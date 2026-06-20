@extends('layouts.public')

@section('title', 'Check Status | '.config('business.name'))

@section('content')
<div class="max-w-3xl mx-auto px-6 py-16" x-data="statusLookup()">
    <div data-reveal="up" class="text-center mb-12">
        <p class="section-label">Track Your Request</p>
        <h1 class="font-black text-5xl tracking-tight">Check Status</h1>
        <p class="mt-4 text-slate-600 max-w-md mx-auto">Enter the phone number and email you used when requesting a quote.</p>
    </div>

    <form @submit.prevent="lookup()" data-reveal="up" data-reveal-delay="100" class="card p-8 mb-10">
        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                <input type="tel" x-model="phone" placeholder="(909) 555-1234" class="input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                <input type="email" x-model="email" placeholder="you@example.com" class="input" required>
            </div>
        </div>
        <button type="submit" :disabled="loading" class="btn-primary w-full mt-6 py-3.5 text-base flex items-center justify-center gap-2">
            <x-icon name="search" class="w-5 h-5"/>
            <span x-text="loading ? 'Looking up...' : 'Find My Requests'"></span>
        </button>
    </form>

    <p x-show="error" x-text="error" class="text-center text-red-600 mb-8" x-cloak></p>

    <div x-show="searched && inquiries.length === 0 && !loading" x-cloak class="text-center py-12 text-slate-600">
        No requests found for that phone + email combination.<br>
        Double-check the details or call us at {{ config('business.phone') }}.
    </div>

    {{-- Tabs --}}
    <div x-show="inquiries.length > 0" x-cloak class="flex gap-2 mb-4 border-b border-gray-200 pb-2">
        <template x-for="t in ['active','completed','all']" :key="t">
            <button @click="tab = t"
                    class="px-4 py-1.5 text-sm rounded-full border transition-colors capitalize"
                    :class="tab === t ? 'bg-[#EAB308] text-charcoal-900 border-[#EAB308]' : 'border-gray-300 text-slate-600 hover:bg-gray-100'"
                    x-text="t"></button>
        </template>
    </div>

    {{-- Results --}}
    <div x-show="inquiries.length > 0" x-cloak class="space-y-6">
        <h2 class="font-bold text-xl px-1">Your Requests (<span x-text="filtered.length"></span>)</h2>

        <template x-for="inq in filtered" :key="inq.id">
            <div class="card p-7">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="font-mono text-orange-600 text-sm mb-1" x-text="inq.ref"></div>
                        <div class="font-semibold text-xl text-slate-900" x-text="inq.name"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                              :class="statusClass(inq.status)" x-text="statusLabel(inq.status)"></span>
                    </div>
                </div>

                <div class="text-sm text-slate-600 mb-4">
                    <span class="capitalize" x-text="serviceLabel(inq.service_type)"></span> &bull; Submitted <span x-text="date(inq.created_at)"></span>
                    <span x-show="inq.confirmed_date_time" class="ml-3 text-green-700 font-medium">Pickup: <span x-text="dateTime(inq.confirmed_date_time)"></span></span>
                </div>

                <p x-show="inq.description" class="text-slate-700 mb-5" x-text="inq.description"></p>

                <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-600 mb-4">
                    <div x-show="inq.zip_code">Zip: <span class="text-slate-900" x-text="inq.zip_code"></span></div>
                    <div x-show="inq.preferred_day">Pref. Day: <span class="text-slate-900" x-text="inq.preferred_day"></span></div>
                    <div x-show="inq.preferred_time">Pref. Time: <span class="text-slate-900" x-text="inq.preferred_time"></span></div>
                </div>

                <div x-show="inq.photo_base64" class="mb-5">
                    <div class="text-xs uppercase tracking-widest text-slate-500 mb-2">Photo you provided</div>
                    <img :src="inq.photo_base64 ? ('data:' + inq.photo_mime + ';base64,' + inq.photo_base64) : ''" alt="Your photo" class="max-h-72 rounded-xl border border-gray-200">
                </div>

                <div x-show="inq.admin_notes" class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r">
                    <div class="text-xs font-bold text-amber-600 mb-1">UPDATE FROM OUR TEAM</div>
                    <p class="text-slate-800 whitespace-pre-wrap" x-text="inq.admin_notes"></p>
                </div>

                <div x-show="inq.initial_estimated_quote" class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs uppercase tracking-widest text-emerald-600 font-bold mb-1.5">INITIAL QUOTE</div>
                    <div class="text-lg font-semibold text-emerald-700">$<span x-text="money(inq.initial_estimated_quote)"></span></div>
                    <div class="text-xs text-slate-500 mt-0.5">Estimate provided when you submitted this request</div>
                </div>

                <div x-show="inq.status === 'completed' && inq.payment_method" class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs uppercase tracking-widest text-emerald-600 font-bold mb-1.5">PAYMENT</div>
                    <div class="text-sm">
                        <div><span class="text-slate-600">Method:</span> <span class="font-medium text-emerald-700" x-text="inq.payment_method"></span></div>
                        <div x-show="inq.payment_date" class="mt-0.5"><span class="text-slate-600">Date:</span> <span class="text-slate-800" x-text="dateLong(inq.payment_date)"></span></div>
                        <div x-show="inq.payment_notes" class="mt-1 text-xs text-slate-500">Ref: <span x-text="inq.payment_notes"></span></div>
                    </div>
                </div>

            </div>
        </template>
    </div>
</div>
@endsection
