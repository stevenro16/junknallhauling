@php
    $tableData = $inquiries->map(fn ($i) => [
        'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'phone' => $i->phone, 'email' => $i->email,
        'status' => $i->status, 'service_type' => $i->service_type, 'zip_code' => $i->zip_code,
        'confirmed_date_time' => $i->confirmed_date_time, 'quoted_price' => $i->quoted_price,
        'quote_verified' => $i->quote_verified, 'address_verified' => $i->address_verified,
        'date_time_verified' => $i->date_time_verified, 'payment_method' => $i->payment_method,
        'created_at' => $i->created_at,
    ])->values();
@endphp

<div x-data="inquiryDashboard({
        inquiries: @js($tableData),
        createUrl: '{{ route('admin.api.inquiries.store') }}',
        detailBase: '{{ route('admin.inquiries.show', ['id' => '__ID__']) }}',
    })">

    {{-- Workqueue cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <button @click="setFilter('active')" class="card-light p-4 text-left hover:border-[#EAB308]/40 transition-colors">
            <div class="text-xs uppercase tracking-widest text-gray-500">New / Reviewing / Quoted</div>
            <div class="text-3xl font-black text-blue-600 mt-1">{{ $counts['new'] }}</div>
        </button>
        <button @click="setFilter('scheduled')" class="card-light p-4 text-left hover:border-[#EAB308]/40 transition-colors">
            <div class="text-xs uppercase tracking-widest text-gray-500">Scheduled</div>
            <div class="text-3xl font-black text-purple-600 mt-1">{{ $counts['scheduled'] }}</div>
        </button>
        <button @click="setFilter('service_performed')" class="card-light p-4 text-left hover:border-[#EAB308]/40 transition-colors">
            <div class="text-xs uppercase tracking-widest text-gray-500">Pending Payment</div>
            <div class="text-3xl font-black text-teal-600 mt-1">{{ $counts['pending'] }}</div>
        </button>
        <button @click="setFilter('completed')" class="card-light p-4 text-left hover:border-[#EAB308]/40 transition-colors">
            <div class="text-xs uppercase tracking-widest text-gray-500">Completed (30d)</div>
            <div class="text-3xl font-black text-green-700 mt-1">{{ $counts['completed30'] }}</div>
        </button>
    </div>

    {{-- Toolbar --}}
    <div class="card-light p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" x-model="search" placeholder="Name, phone, email, ref, service..." class="input-light">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select x-model="filter" class="input-light">
                    <option value="active">Active</option>
                    <option value="all">All</option>
                    <option value="new">New</option>
                    <option value="left_voicemail">Left Voicemail</option>
                    <option value="reviewing">Reviewing</option>
                    <option value="quoted">Quoted</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="service_performed">Service Performed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Submitted on</label>
                <input type="date" x-model="dateFilter" class="input-light">
            </div>
            <button type="button" @click="showNew = true" class="btn-primary py-2 px-4 text-sm">
                <x-icon name="plus" class="w-4 h-4"/> New Quote
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-light overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Ref</th>
                        <th class="text-left px-4 py-3">Confirmed</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-center px-4 py-3">Verified</th>
                        <th class="text-left px-4 py-3">Service</th>
                        <th class="text-left px-4 py-3">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="i in filtered" :key="i.id">
                        <tr class="hover:bg-amber-50/40 cursor-pointer" @click="window.location.href = detailUrl(i.id)">
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusClass(i.status)" x-text="statusLabel(i.status)"></span></td>
                            <td class="px-4 py-3 font-mono text-amber-700 text-xs" x-text="i.ref"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="i.confirmed_date_time ? dateTime(i.confirmed_date_time) : '—'"></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900" x-text="i.name || '(no name)'"></div>
                                <div class="text-xs text-gray-500" x-text="i.phone"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <x-icon name="map-pin" class="w-3.5 h-3.5" ::class="i.address_verified ? 'text-green-600' : 'text-gray-300'"/>
                                    <x-icon name="calendar" class="w-3.5 h-3.5" ::class="i.date_time_verified ? 'text-green-600' : 'text-gray-300'"/>
                                    <x-icon name="dollar-sign" class="w-3.5 h-3.5" ::class="i.quote_verified ? 'text-green-600' : 'text-gray-300'"/>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 capitalize" x-text="serviceLabel(i.service_type)"></td>
                            <td class="px-4 py-3">
                                <span x-show="i.payment_method" class="text-emerald-600 text-xs font-medium" x-text="i.payment_method"></span>
                                <span x-show="!i.payment_method" class="text-gray-400 text-xs">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div x-show="filtered.length === 0" class="text-center text-gray-500 py-12">No quotes match the current filters.</div>
        </div>
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
                    <div class="space-y-1.5">
                        <template x-for="m in phoneMatches" :key="m.id">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="font-mono text-[10px] text-gray-500" x-text="m.ref"></span>
                                <span class="flex-1 text-gray-800" x-text="m.name || '(no name)'"></span>
                                <a :href="detailUrl(m.id)" class="p-1 text-amber-600 hover:text-amber-700" title="Open work order"><x-icon name="eye" class="w-4 h-4"/></a>
                                <button type="button" @click="cloneFrom(m)" class="p-1 text-amber-600 hover:text-amber-700" title="Clone customer info"><x-icon name="plus" class="w-4 h-4"/></button>
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
