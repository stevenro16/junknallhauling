@extends('layouts.admin')

@section('title', 'Customers — '.config('business.name'))

@section('admin-content')
<div class="max-w-5xl mx-auto" x-data="customerLookup({
        inquiries: @js($inquiries),
        detailBase: '{{ route('admin.inquiries.show', '__ID__') }}',
     })">

    <div class="mb-6 print:hidden">
        <h2 class="text-2xl font-semibold">Customers</h2>
        <p class="text-sm text-gray-500">Search by phone or email to see a customer's history and analytics.</p>
    </div>

    {{-- Search + results (collapses once a customer is selected; tap the header to reopen) --}}
    <div class="card-light p-4 mb-6 print:hidden">
        <button type="button" @click="listExpanded = !listExpanded" class="w-full flex items-center justify-between gap-3 text-left">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-800">Find a customer</div>
                <div class="text-xs text-gray-500 truncate">
                    <span x-show="!listExpanded && selected" x-cloak>Showing <span class="font-medium text-gray-700" x-text="selected ? (selected.name || selected.phone || '(no name)') : ''"></span> &middot; tap to search another</span>
                    <span x-show="listExpanded || !selected">Search by phone, email, or name</span>
                </div>
            </div>
            <x-icon name="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" ::class="listExpanded && 'rotate-180'"/>
        </button>

        <div x-show="listExpanded" x-cloak x-transition.opacity class="mt-3">
            <div class="relative">
                <x-icon name="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"/>
                <input type="search" x-model="query" placeholder="Phone number, email, or name…" class="input-light pl-9">
            </div>

            <div class="mt-3">
                <div class="text-[11px] uppercase tracking-widest text-gray-400 mb-2" x-text="query.trim().length < 2 ? 'Recent customers' : (results.length + ' match' + (results.length === 1 ? '' : 'es'))"></div>
                <div class="space-y-1.5">
                    <template x-for="c in results" :key="c.key">
                        <button type="button" @click="select(c.key)"
                                class="w-full text-left rounded-lg border p-3 transition-colors"
                                :class="selectedKey === c.key ? 'border-amber-400 bg-amber-50/60' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 truncate" x-text="c.name || '(no name)'"></div>
                                    <div class="text-xs text-gray-500 truncate">
                                        <span x-text="c.phone || '—'"></span><span x-show="c.email"> · <span x-text="c.email"></span></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-semibold text-gray-700" x-text="c.count + (c.count === 1 ? ' quote' : ' quotes')"></div>
                                    <div class="text-[10px] text-gray-400" x-text="'Last ' + date(c.lastSeen)"></div>
                                </div>
                            </div>
                        </button>
                    </template>
                    <div x-show="results.length === 0" class="text-sm text-gray-400 py-4 text-center">No customers found.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Empty prompt --}}
    <div x-show="!selected" x-cloak class="card-light p-10 text-center text-gray-400 print:hidden">
        Select a customer above to view their analytics and quotes.
    </div>

    {{-- Selected customer report --}}
    <div x-show="selected" x-cloak>
        {{-- Print-only header --}}
        <div class="hidden print:block mb-4">
            <div class="text-lg font-bold">{{ config('business.name') }} — Customer Report</div>
            <div class="text-xs text-gray-500">Generated {{ now()->format('M j, Y g:i A') }}</div>
        </div>

        {{-- Profile + quick actions --}}
        <div class="card-light p-5 mb-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-gray-900" x-text="selected.name || '(no name)'"></h3>
                    <div class="mt-1 text-sm text-gray-600 space-y-0.5">
                        <div x-show="selected.phone"><span class="text-gray-400">Phone:</span> <span x-text="selected.phone"></span></div>
                        <div x-show="selected.email"><span class="text-gray-400">Email:</span> <span x-text="selected.email"></span></div>
                        <div x-show="selected.address"><span class="text-gray-400">Address:</span> <span x-text="selected.address"></span></div>
                        <div><span class="text-gray-400">Preferred contact:</span> <span class="capitalize" x-text="selected.preferred"></span></div>
                        <div><span class="text-gray-400">Customer since:</span> <span x-text="date(selected.firstSeen)"></span> · <span class="text-gray-400">Last activity:</span> <span x-text="date(selected.lastSeen)"></span></div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 print:hidden">
                    <button type="button" @click="contact('tel')" x-show="selected.phone" class="btn-outline text-xs px-3 py-1.5 inline-flex items-center gap-1"><x-icon name="phone" class="w-3.5 h-3.5"/> Call</button>
                    <button type="button" @click="contact('sms')" x-show="selected.phone" class="btn-outline text-xs px-3 py-1.5">Text</button>
                    <button type="button" @click="contact('email')" x-show="selected.email" class="btn-outline text-xs px-3 py-1.5 inline-flex items-center gap-1"><x-icon name="mail" class="w-3.5 h-3.5"/> Email</button>
                    <button type="button" @click="print()" class="btn-primary text-xs px-3 py-1.5 inline-flex items-center gap-1"><x-icon name="file-text" class="w-3.5 h-3.5"/> Print report</button>
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
            <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Total Quotes</div><div class="text-3xl font-black text-blue-600 mt-1" x-text="selected.count"></div></div>
            <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Completed</div><div class="text-3xl font-black text-green-700 mt-1" x-text="selected.completedCount"></div></div>
            <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Lifetime Revenue</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(selected.revenue)"></span></div></div>
            <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Avg Job Value</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(selected.avg)"></span></div></div>
            <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Outstanding</div><div class="text-3xl font-black text-amber-600 mt-1">$<span x-text="money(selected.outstanding)"></span></div></div>
        </div>

        {{-- Breakdowns --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="card-light p-5">
                <div class="text-sm font-semibold text-gray-700 mb-4">Services &amp; Equipment</div>
                <div class="space-y-3">
                    <template x-for="row in selectedByService" :key="row.label">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1 gap-2">
                                <span class="text-gray-700 font-medium truncate" x-text="row.label"></span>
                                <span class="text-gray-500 shrink-0"><span x-text="row.count"></span> <span x-text="row.count === 1 ? 'job' : 'jobs'"></span> · <span class="font-semibold text-emerald-600">$<span x-text="money(row.revenue)"></span></span></span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" :style="`width:${Math.max(2, row.pct)}%`"></div></div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="card-light p-5">
                <div class="text-sm font-semibold text-gray-700 mb-4">Status Mix</div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="row in selectedByStatus" :key="row.key">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-semibold" :class="statusClass(row.key)">
                            <span x-text="row.label"></span><span class="opacity-70" x-text="row.count"></span>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        {{-- Quotes list --}}
        <div class="card-light overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 text-sm font-semibold text-gray-700">Quotes (<span x-text="selectedQuotes.length"></span>)</div>
            <div class="divide-y divide-gray-100">
                <template x-for="i in selectedQuotes" :key="i.id">
                    <a :href="detailUrl(i.id)" class="flex items-center gap-3 p-4 hover:bg-amber-50/40 print:hover:bg-transparent">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0" :class="statusClass(i.status)" x-text="statusLabel(i.status)"></span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-800 capitalize truncate">
                                <span class="font-mono text-amber-700 text-xs mr-1" x-text="i.ref"></span>
                                <span x-text="i.equipment_type || serviceLabel(i.service_type)"></span>
                                <span x-show="i.equipment_type && rentalLabel(i)" class="text-gray-400 text-xs"> · <span x-text="rentalLabel(i)"></span></span>
                            </div>
                            <div class="text-[11px] text-gray-500 mt-0.5">
                                <span x-text="'Created ' + date(i.created_at)"></span>
                                <span x-show="i.confirmed_date_time"> · Visit <span x-text="dateTime(i.confirmed_date_time)"></span></span>
                                <span x-show="i.payment_method" class="text-emerald-600"> · <span x-text="i.payment_method"></span></span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div x-show="i.quoted_price" class="text-sm font-semibold text-emerald-600">$<span x-text="money(i.quoted_price)"></span></div>
                        </div>
                    </a>
                </template>
                <div x-show="selectedQuotes.length === 0" class="p-6 text-center text-sm text-gray-400">No quotes.</div>
            </div>
        </div>
    </div>
</div>
@endsection
