@php
    $tableData = $inquiries->map(fn ($i) => [
        'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'phone' => $i->phone, 'email' => $i->email,
        'status' => $i->status, 'service_type' => $i->service_type, 'zip_code' => $i->zip_code,
        'equipment_type' => $i->equipment_type,
        'equipment_rental_duration' => $i->equipment_rental_duration,
        'equipment_rental_unit' => $i->equipment_rental_unit,
        'confirmed_date_time' => $i->confirmed_date_time, 'quoted_price' => $i->quoted_price,
        'payment_method' => $i->payment_method,
        'created_at' => $i->created_at, 'updated_at' => $i->updated_at,
        'agreement' => $i->rentalAgreements->isEmpty()
            ? 'none'
            : ($i->rentalAgreements->contains(fn ($a) => $a->signed_at) ? 'signed' : 'pending'),
    ])->values();
@endphp

<div x-data="inquiryDashboard({
        inquiries: @js($tableData),
        createUrl: '{{ route('admin.api.inquiries.store') }}',
        cloneUrl: '{{ route('admin.api.inquiries.clone', ['id' => '__ID__']) }}',
        detailBase: '{{ route('admin.inquiries.show', ['id' => '__ID__']) }}',
    })">

    {{-- Workqueue cards — click to filter the list below --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <button @click="setFilter('new')" class="card-light p-4 text-left transition-colors hover:border-[#EAB308]/40" :class="filter === 'new' ? 'ring-2 ring-amber-400 border-amber-400' : ''">
            <div class="text-xs uppercase tracking-widest text-gray-500">New</div>
            <div class="text-3xl font-black text-blue-600 mt-1" x-text="countNew"></div>
        </button>
        <button @click="setFilter('reviewing_quoted')" class="card-light p-4 text-left transition-colors hover:border-[#EAB308]/40" :class="filter === 'reviewing_quoted' ? 'ring-2 ring-amber-400 border-amber-400' : ''">
            <div class="text-xs uppercase tracking-widest text-gray-500">Reviewing / Quoted</div>
            <div class="text-3xl font-black text-indigo-600 mt-1" x-text="countReviewingQuoted"></div>
        </button>
        <button @click="setFilter('scheduled')" class="card-light p-4 text-left transition-colors hover:border-[#EAB308]/40" :class="filter === 'scheduled' ? 'ring-2 ring-amber-400 border-amber-400' : ''">
            <div class="text-xs uppercase tracking-widest text-gray-500">Scheduled</div>
            <div class="text-3xl font-black text-purple-600 mt-1" x-text="countScheduled"></div>
        </button>
        <button @click="setFilter('service_performed')" class="card-light p-4 text-left transition-colors hover:border-[#EAB308]/40" :class="filter === 'service_performed' ? 'ring-2 ring-amber-400 border-amber-400' : ''">
            <div class="text-xs uppercase tracking-widest text-gray-500">Service Performed</div>
            <div class="text-3xl font-black text-teal-600 mt-1" x-text="countServicePerformed"></div>
        </button>
        <button @click="setFilter('completed30')" class="card-light p-4 text-left transition-colors hover:border-[#EAB308]/40" :class="filter === 'completed30' ? 'ring-2 ring-amber-400 border-amber-400' : ''">
            <div class="text-xs uppercase tracking-widest text-gray-500">Completed (30 days)</div>
            <div class="text-3xl font-black text-green-700 mt-1" x-text="countCompleted30"></div>
        </button>
    </div>

    {{-- Action bar — the quick-filter cards above are the navigation --}}
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-sm text-gray-500"><span class="font-semibold text-gray-700" x-text="filtered.length"></span> <span x-text="filtered.length === 1 ? 'quote' : 'quotes'"></span></div>
        <button type="button" @click="showNew = true" class="btn-primary py-2 px-4 text-sm">
            <x-icon name="plus" class="w-4 h-4"/> New Quote
        </button>
    </div>

    {{-- Work orders — table on desktop, stacked cards on mobile --}}
    <div class="card-light overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Ref</th>
                        <th class="text-left px-4 py-3">Confirmed</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Service</th>
                        <th class="text-left px-4 py-3">Payment</th>
                        <th class="text-center px-4 py-3">Agreement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="i in filtered" :key="i.id">
                        <tr class="hover:bg-amber-50/40 cursor-pointer" @click="window.location.href = detailUrl(i.id)">
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusClass(i.status)" x-text="statusLabel(i.status)"></span></td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-amber-700 text-xs" x-text="i.ref"></div>
                                <div class="text-[10px] text-gray-400 mt-0.5" x-show="i.updated_at" x-text="'Updated ' + dateTime(i.updated_at)"></div>
                            </td>
                            <td class="px-4 py-3 text-gray-600" x-text="i.confirmed_date_time ? dateTime(i.confirmed_date_time) : '—'"></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900" x-text="i.name || '(no name)'"></div>
                                <div class="text-xs text-gray-500" x-text="i.phone"></div>
                                <div class="text-xs text-gray-500" x-show="i.email" x-text="i.email"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-800 capitalize" x-text="i.equipment_type || serviceLabel(i.service_type)"></div>
                                <div class="text-xs text-gray-500 mt-0.5" x-show="i.equipment_type">
                                    Equipment Rental<span x-show="rentalLabel(i)"> &middot; <span x-text="rentalLabel(i)"></span></span>
                                </div>
                                <div class="text-xs font-medium text-emerald-600 mt-0.5" x-show="i.quoted_price">$<span x-text="money(i.quoted_price)"></span></div>
                            </td>
                            <td class="px-4 py-3">
                                <span x-show="i.payment_method" class="text-emerald-600 text-xs font-medium" x-text="i.payment_method"></span>
                                <span x-show="!i.payment_method" class="text-gray-400 text-xs">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="i.agreement !== 'none'" x-cloak
                                      :title="i.agreement === 'signed' ? 'Agreement signed' : 'Agreement sent — awaiting signature'">
                                    <x-icon name="file-text" class="w-4 h-4 inline-block" ::class="i.agreement === 'signed' ? 'text-green-600' : 'text-gray-400'"/>
                                </span>
                                <span x-show="i.agreement === 'none'" class="text-gray-300 text-xs">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-gray-100">
            <template x-for="i in filtered" :key="i.id">
                <a :href="detailUrl(i.id)" class="block p-4 active:bg-amber-50/60">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900 truncate" x-text="i.name || '(no name)'"></div>
                            <div class="text-xs text-gray-500" x-text="i.phone"></div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0" :class="statusClass(i.status)" x-text="statusLabel(i.status)"></span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        <span class="font-mono text-amber-700" x-text="i.ref"></span>
                        <span class="text-gray-700 capitalize" x-text="i.equipment_type || serviceLabel(i.service_type)"></span>
                        <span x-show="i.quoted_price" class="font-medium text-emerald-600">$<span x-text="money(i.quoted_price)"></span></span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                        <span x-show="i.confirmed_date_time"><span class="text-gray-400">Visit:</span> <span x-text="dateTime(i.confirmed_date_time)"></span></span>
                        <span x-show="i.payment_method" class="text-emerald-600 font-medium" x-text="i.payment_method"></span>
                        <span x-show="i.agreement !== 'none'" x-cloak class="inline-flex items-center gap-0.5" :class="i.agreement === 'signed' ? 'text-green-600' : 'text-gray-400'">
                            <x-icon name="file-text" class="w-3 h-3"/> <span x-text="i.agreement === 'signed' ? 'Signed' : 'Sent'"></span>
                        </span>
                    </div>
                </a>
            </template>
        </div>

        <div x-show="filtered.length === 0" class="text-center text-gray-500 py-12">No quotes in this view.</div>
    </div>

    {{-- New Quote Modal --}}
    <div x-show="showNew" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" @click.self="showNew = false">
        <div class="w-full max-w-lg bg-white rounded-xl border border-gray-200 shadow-xl p-6 relative">
            <button @click="showNew = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><x-icon name="x" class="w-5 h-5"/></button>
            <h3 class="text-xl font-semibold text-gray-800 mb-1">New Quote</h3>
            <p class="text-sm text-gray-500 mb-5">Start by entering the customer's phone number.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-700 mb-1.5">Phone Number <span class="text-red-500">*</span></label>
                    <input type="tel" x-model="nq.phone" placeholder="(909) 555-1234" class="input-light" autofocus>
                </div>

                {{-- Previous customer matches --}}
                <div x-show="phoneMatches.length > 0" x-cloak class="border border-amber-200 bg-amber-50/60 rounded-lg p-3">
                    <div class="text-xs uppercase tracking-widest text-amber-700 mb-2">Previous customers with this number</div>
                    <div class="space-y-2">
                        <template x-for="m in phoneMatches" :key="m.id">
                            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white/70 border border-amber-100 p-2">
                                <div class="flex-1 min-w-0 text-sm pl-1">
                                    <span class="font-mono text-[10px] text-gray-500 mr-1" x-text="m.ref"></span>
                                    <span class="text-gray-800" x-text="m.name || '(no name)'"></span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="detailUrl(m.id)" class="inline-flex items-center justify-center gap-1.5 min-h-[40px] px-3 rounded-lg border border-amber-300 text-amber-700 text-xs font-medium hover:bg-amber-100 active:scale-[0.98] transition" title="Open work order">
                                        <x-icon name="eye" class="w-4 h-4"/> View
                                    </a>
                                    <button type="button" @click="cloneQuote(m)" :disabled="nq.loading" class="inline-flex items-center justify-center gap-1.5 min-h-[40px] px-3 rounded-lg bg-amber-500 text-white text-xs font-semibold hover:bg-amber-600 active:scale-[0.98] transition disabled:opacity-60" title="Create a new quote with all of this quote's information">
                                        <x-icon name="plus" class="w-4 h-4"/> Clone
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Create as new customer --}}
                <div class="pt-2 border-t border-gray-200">
                    <div class="text-xs uppercase tracking-widest text-gray-500 mb-2">Or create as new customer</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" x-model="nq.name" placeholder="Name" class="input-light">
                        <input type="email" x-model="nq.email" placeholder="Email" class="input-light">
                        <input type="text" x-model="nq.zip" placeholder="Zip" class="input-light">
                    </div>
                </div>

                <p x-show="nq.error" x-text="nq.error" class="text-red-500 text-sm" x-cloak></p>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="showNew = false" class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="button" @click="createQuote()" :disabled="nq.loading" class="btn-primary flex-1 py-2.5 text-sm"><span x-text="nq.loading ? 'Creating...' : 'Create Quote'"></span></button>
                </div>
            </div>
        </div>
    </div>
</div>
