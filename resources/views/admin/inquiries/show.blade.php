@extends('layouts.admin')

@section('title', $inquiry->ref.' — '.config('business.name'))

@section('admin-content')
<div x-data="inquiryDetail({
        inquiry: @js($inquiry),
        equipment: @js($equipment),
        allInquiries: @js($allInquiries),
        history: @js($history),
        urls: {
            update: '{{ route('admin.api.inquiries.update', $inquiry->id) }}',
            history: '{{ route('admin.api.inquiries.history', $inquiry->id) }}',
            audit: '{{ route('admin.api.inquiries.audit', $inquiry->id) }}',
        },
    })" class="w-full pt-1 pb-8">

    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-amber-600 hover:text-amber-700 transition-colors">&larr; Back to list</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

        {{-- Column 1: customer + job details --}}
        <div class="space-y-5">

            {{-- Card 1: Customer --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="font-mono text-amber-700 text-sm tracking-widest bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" x-text="inquiry.ref"></span>
                            <h1 class="text-gray-900 text-3xl tracking-widest font-bold" x-text="inquiry.name"></h1>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusClass(status)" x-text="statusLabel(status)"></span>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" @click="isEditingCustomer = !isEditingCustomer" class="p-1.5 rounded hover:bg-gray-200 text-amber-600 transition-colors" title="Edit customer fields"><x-icon name="pencil" class="w-4 h-4"/></button>
                                <button type="button" @click="togglePreferredContact()" class="p-1.5 rounded-full border border-gray-300 hover:border-amber-400 text-amber-600 hover:text-amber-700 transition-all active:scale-95" title="Toggle preferred contact">
                                    <x-icon name="phone" class="w-4 h-4" x-show="preferredContactMethod === 'phone'"/>
                                    <x-icon name="mail" class="w-4 h-4" x-show="preferredContactMethod === 'email'" x-cloak/>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="tel" x-model="phone" class="input-light text-sm py-1.5 w-full" placeholder="Phone number"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" x-model="email" class="input-light text-sm py-1.5 w-full" placeholder="Email address"></div>
                    </div>

                    <template x-if="isEditingCustomer">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label><input type="text" x-model="customerZip" class="input-light text-sm py-1.5 w-full" placeholder="Zip code"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Preferred Day</label><input type="text" x-model="customerPreferredDay" class="input-light text-sm py-1.5 w-full" placeholder="e.g. Monday"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label><input type="text" x-model="customerPreferredTime" class="input-light text-sm py-1.5 w-full" placeholder="e.g. Morning"></div>
                        </div>
                    </template>
                    <template x-if="!isEditingCustomer">
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-700">
                            <div x-show="customerZip"><span class="text-gray-500">Zip:</span> <span x-text="customerZip"></span></div>
                            <div x-show="customerPreferredDay"><span class="text-gray-500">Preferred Day:</span> <span x-text="customerPreferredDay"></span></div>
                            <div x-show="customerPreferredTime"><span class="text-gray-500">Preferred Time:</span> <span x-text="customerPreferredTime"></span></div>
                            <div class="text-gray-500">First quote: <span x-text="dateTime(inquiry.created_at)"></span></div>
                        </div>
                    </template>

                    <template x-if="previousCustomerAddresses.length > 0">
                        <div class="pt-3 border-t border-gray-200">
                            <div class="text-xs uppercase tracking-widest text-gray-500 mb-1.5">Previous addresses for this customer</div>
                            <div class="space-y-1 text-sm">
                                <template x-for="inq in previousCustomerAddresses.slice(0, 6)" :key="inq.id">
                                    <div class="flex items-start gap-2 text-gray-700 text-sm">
                                        <span class="font-mono text-[10px] text-gray-500 w-20 shrink-0 pt-0.5" x-text="date(inq.created_at)"></span>
                                        <span class="flex-1 leading-snug break-words" x-text="inq.address"></span>
                                        <button type="button" @click="openAddressInMaps(inq.address)" class="mt-0.5 p-1 text-amber-500 opacity-70 hover:opacity-100" title="Open in Google Maps"><x-icon name="map" class="w-3.5 h-3.5"/></button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Card 2: Job Details --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex items-center gap-3 mb-4"><div class="text-lg font-semibold text-amber-700">Job Details</div><div class="h-px flex-1 bg-gray-200"></div></div>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Service Needed</label>
                            <select x-model="serviceType" class="input-light text-sm py-2 w-full">
                                <option value="junk-removal">Junk Removal</option>
                                <option value="10yd-dumpster">10 Yard Dumpster Rental</option>
                                <option value="20yd-dumpster">20 Yard Dumpster Rental</option>
                                <option value="equipment">Equipment Rental</option>
                                <option value="other">Other / Not Sure</option>
                            </select>
                            <template x-if="inquiry.photo_base64 && inquiry.photo_mime">
                                <div class="mt-2">
                                    <button type="button" @click="showPhotoModal = true" class="block overflow-hidden rounded-lg border border-gray-300 hover:border-[#F8C820] transition-colors" title="Click to view full size">
                                        <img :src="'data:' + inquiry.photo_mime + ';base64,' + inquiry.photo_base64" alt="Customer photo" class="w-full max-h-24 object-cover">
                                    </button>
                                    <div class="text-[10px] text-gray-500 mt-0.5">Photo — click to enlarge</div>
                                </div>
                            </template>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-1.5">Quote Description</div>
                            <template x-if="inquiry.description">
                                <div class="text-sm text-gray-800 whitespace-pre-wrap bg-gray-50 p-2.5 rounded-lg border border-gray-200 leading-relaxed" x-text="inquiry.description"></div>
                            </template>
                            <template x-if="!inquiry.description"><div class="text-sm text-gray-500 italic">No description provided.</div></template>
                        </div>
                    </div>

                    {{-- Equipment row --}}
                    <div class="grid grid-cols-2 gap-4" x-show="isEquipment" x-cloak>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Equipment Needed</label>
                            <select x-model="equipmentType" class="input-light text-sm py-2 w-full">
                                <option value="">Select equipment type...</option>
                                <template x-for="opt in equipmentOptions" :key="opt.id">
                                    <option :value="opt.name" x-text="opt.name + (opt.avg_cost_per_hour ? ' (~$' + opt.avg_cost_per_hour + '/hr)' : '')"></option>
                                </template>
                                <option value="__other__">Other (specify below)</option>
                            </select>
                            <input x-show="equipmentType === '__other__'" type="text" @input="equipmentType = $event.target.value" placeholder="Specify equipment type" class="input-light text-sm py-2 mt-2 w-full" x-cloak>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Requested Rental Duration</label>
                            <div class="flex gap-2">
                                <input type="number" x-model="equipmentRentalDuration" class="input-light text-sm py-2 w-20" placeholder="Qty">
                                <select x-model="equipmentRentalUnit" class="input-light text-sm py-2 flex-1">
                                    <option value="">Unit</option><option value="hours">Hours</option><option value="days">Days</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Initial quote block --}}
                    <div x-show="isEquipment && (equipmentType || equipmentRentalDuration || inquiry.initial_estimated_quote)" x-cloak class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-50">
                        <div class="flex items-baseline justify-between mb-1">
                            <div class="text-xs uppercase tracking-[0.5px] text-emerald-600 font-semibold">Initial Quote Presented to Customer</div>
                            <div class="text-[10px] text-emerald-600/70">from quote form</div>
                        </div>
                        <template x-if="inquiry.initial_estimated_quote">
                            <div class="text-3xl font-black text-emerald-600 tracking-tighter">$<span x-text="money(inquiry.initial_estimated_quote)"></span></div>
                        </template>
                        <template x-if="!inquiry.initial_estimated_quote"><div class="text-sm text-gray-500">No initial quote recorded for this submission</div></template>
                        <div class="text-[10px] text-emerald-600/80 mt-1 leading-tight">This is the exact amount the customer saw and could override on the public request form.</div>
                        <template x-if="currentCatalogInitialQuote !== null">
                            <div class="mt-3 pt-3 border-t border-emerald-500/20 text-xs">
                                <div class="text-emerald-600/70 mb-0.5">Current catalog rates would calculate to:</div>
                                <div class="font-semibold text-emerald-700">$<span x-text="money(currentCatalogInitialQuote)"></span><span x-show="equipmentRentalUnit === 'days'" class="text-[10px] ml-1 text-emerald-600/60">(using today's rates)</span></div>
                                <div x-show="equipmentRentalUnit === 'days'" class="mt-2 flex items-center gap-2 text-[10px] text-emerald-600/70">
                                    <span>Hours per day assumption:</span>
                                    <input type="number" x-model="adminHoursPerDay" class="input-light text-xs py-0.5 w-14 text-center" min="1" max="24">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Pickup address --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pickup Address</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="address" placeholder="123 Main St, Yucaipa..." class="input-light text-sm py-2 flex-1">
                            <button type="button" @click="openInGoogleMaps()" :disabled="!address.trim()" class="btn-outline !px-3 !py-2" title="Open in Google Maps"><x-icon name="map" class="w-4 h-4"/></button>
                        </div>
                        <template x-if="previousCustomerAddresses.length > 0">
                            <div class="mt-2">
                                <p class="text-[10px] uppercase tracking-widest text-gray-500 mb-1.5">Previous addresses</p>
                                <div class="space-y-1">
                                    <template x-for="(inq, i) in previousCustomerAddresses" :key="i">
                                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg border transition-colors" :class="address.trim().toLowerCase() === inq.address.trim().toLowerCase() ? 'border-[#F8C820]/40 bg-[#F8C820]/5' : 'border-gray-200 bg-gray-50'">
                                            <button type="button" @click="address = inq.address" :disabled="address.trim().toLowerCase() === inq.address.trim().toLowerCase()" class="shrink-0 w-5 h-5 flex items-center justify-center rounded-full border border-amber-400 text-amber-600 hover:bg-amber-50 disabled:opacity-30 transition-colors" title="Use this address"><x-icon name="plus" class="w-3 h-3"/></button>
                                            <span class="text-xs flex-1 truncate" :class="address.trim().toLowerCase() === inq.address.trim().toLowerCase() ? 'text-amber-700 font-medium' : 'text-gray-500'" x-text="inq.address"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Service notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Service Notes <span class="text-xs text-emerald-600">(visible to customer)</span></label>
                        <textarea rows="2" x-model="adminNotes" placeholder="Updates, instructions, or notes for the customer..." class="input-light text-sm py-2 w-full resize-none"></textarea>
                    </div>

                    <div>
                        <button type="button" @click="save()" :disabled="saving" class="w-full btn-primary text-sm py-2"><span x-text="saving ? 'Saving...' : 'Save Job Details'"></span></button>
                    </div>
                </div>
            </div>

            </div>{{-- /column 1 --}}

            {{-- Column 2: scheduling + payment --}}
            <div class="space-y-5">
            {{-- Card: Visit Date & Time --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex items-center gap-3 mb-4"><div class="text-lg font-semibold text-amber-700">Visit Date &amp; Time</div><div class="h-px flex-1 bg-gray-200"></div></div>
                <div class="space-y-3">
                    {{-- Date / Time / Duration --}}
                    <div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Date</div>
                                <input type="date" :value="datePart(confirmedDateTime)" @change="setConfirmedDate($event.target.value)" class="input-light text-sm py-2 w-full">
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Time</div>
                                <select :value="timePart(confirmedDateTime)" @change="setConfirmedTime($event.target.value)" class="input-light text-sm py-2 w-full">
                                    <option value="">Select time...</option>
                                    <template x-for="slot in TIME_SLOTS" :key="slot"><option :value="slot" x-text="fmtTime12(slot)"></option></template>
                                </select>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Duration</div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="stepDuration(-1)" class="w-7 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">&minus;</button>
                                    <input type="number" x-model="expectedDurationValue" class="input-light text-sm py-2 w-12 text-center px-1" placeholder="—">
                                    <select x-model="expectedDurationUnit" class="input-light text-sm py-2 w-16 px-1"><option value="hours">hrs</option><option value="days">days</option></select>
                                    <button type="button" @click="stepDuration(1)" class="w-7 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">+</button>
                                </div>
                            </div>
                        </div>
                        <template x-if="inquiry.preferred_day || inquiry.preferred_time">
                            <div class="mt-1.5">
                                <div class="text-[10px] text-gray-500 mb-1">Customer prefers: <span x-text="inquiry.preferred_day"></span><span x-show="inquiry.preferred_time"> (<span x-text="inquiry.preferred_time"></span>)</span></div>
                                <template x-if="inquiry.preferred_day">
                                    <div class="flex gap-2 flex-wrap">
                                        <template x-for="(dateStr, index) in getNextTwoOccurrences(inquiry.preferred_day)" :key="index">
                                            <button type="button" @click="pickPreferredDate(dateStr)" class="text-xs px-2.5 py-1 rounded border border-amber-300 text-amber-700 hover:bg-amber-50 active:scale-[0.985] transition-all" x-text="dayLabel(dateStr)"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div>
                        <button type="button" @click="save()" :disabled="saving" class="w-full btn-primary text-sm py-2"><span x-text="saving ? 'Saving...' : 'Save Date & Time'"></span></button>
                    </div>
                </div>
            </div>

            {{-- Card 3: Payment --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex items-center gap-3 mb-4"><div class="text-lg font-semibold text-emerald-600">Payment</div><div class="h-px flex-1 bg-gray-200"></div></div>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Quoted Price</label>
                            <div class="relative"><span class="absolute left-3 top-2.5 text-gray-500">$</span><input type="number" step="0.01" x-model="quotedPrice" placeholder="0.00" class="input-light text-sm py-2 pl-7 w-full"></div>
                            <template x-if="inquiry.initial_estimated_quote"><div class="mt-1 text-[10px] text-gray-500">Customer saw: <span class="font-medium text-emerald-600">$<span x-text="money(inquiry.initial_estimated_quote)"></span></span> initially</div></template>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
                            <select x-model="paymentMethod" @change="paymentMethod !== 'Other' && (paymentMethodOther = '')" class="input-light text-sm py-2 w-full">
                                <option value="">Not yet received</option><option value="Cash">Cash</option><option value="Check">Check</option>
                                <option value="Credit/Debit Card">Credit/Debit Card</option><option value="Venmo">Venmo</option><option value="Zelle">Zelle</option>
                                <option value="PayPal">PayPal</option><option value="Invoice">Invoice (Net 30)</option><option value="Other">Other</option>
                            </select>
                            <input x-show="paymentMethod === 'Other'" type="text" x-model="paymentMethodOther" placeholder="Specify method" class="input-light text-sm py-2 w-full mt-2" x-cloak>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Date</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" :value="datePart(paymentDate)" @change="setPaymentDate($event.target.value)" class="input-light text-sm py-2 w-full">
                                <select :value="timePart(paymentDate)" @change="setPaymentTime($event.target.value)" class="input-light text-sm py-2 w-full">
                                    <option value="">Time...</option>
                                    <template x-for="slot in TIME_SLOTS" :key="slot"><option :value="slot" x-text="fmtTime12(slot)"></option></template>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes / Reference <span class="text-gray-500 text-xs">(check #, tx ID)</span></label>
                            <input type="text" x-model="paymentNotes" placeholder="Check #12345, Venmo XYZ..." class="input-light text-sm py-2 w-full">
                        </div>
                    </div>

                    <div>
                        <button type="button" @click="save()" :disabled="saving" class="w-full btn-primary text-sm py-2"><span x-text="saving ? 'Saving...' : 'Save Payment'"></span></button>
                    </div>
                </div>
            </div>
            </div>{{-- /column 2 --}}

        {{-- Column 3: status timeline, rental agreement, history --}}
        <div class="space-y-4 xl:sticky xl:top-2">
                @include('partials.admin.status-timeline')

                @include('partials.admin.rental-agreement-panel')

                <template x-if="history.length > 0">
                    <div>
                        <div class="text-sm font-medium text-gray-700 mb-2 px-1">Status History</div>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 text-sm max-h-[380px] overflow-auto">
                            <div class="space-y-2">
                                <template x-for="entry in history" :key="entry.id">
                                    <div class="flex items-start gap-3 text-gray-700">
                                        <div class="font-mono text-xs text-gray-500 w-32 shrink-0 pt-0.5" x-text="dateTime(entry.changed_at)"></div>
                                        <div class="flex-1">
                                            <span class="text-gray-500">Status changed from</span>
                                            <span class="font-medium text-[#CA8A04]" x-text="entry.old_status || '—'"></span>
                                            <span class="text-gray-500">&rarr;</span>
                                            <span class="font-medium text-emerald-600" x-text="entry.new_status"></span>
                                            <span class="text-gray-500 text-xs ml-2">by <span x-text="entry.changed_by"></span></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
        </div>{{-- /column 3 --}}
    </div>{{-- /grid --}}

    @include('partials.admin.inquiry-modals')
</div>
@endsection
