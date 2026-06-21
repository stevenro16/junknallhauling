@extends('layouts.admin')

@section('title', $inquiry->ref.' — '.config('business.name'))

@section('admin-content')
<div x-data="inquiryDetail({
        inquiry: @js($inquiry),
        equipment: @js($equipment),
        services: @js($services),
        allInquiries: @js($allInquiries),
        scheduleEvents: @js($scheduleEvents),
        history: @js($history),
        urls: {
            update: '{{ route('admin.api.inquiries.update', $inquiry->id) }}',
            history: '{{ route('admin.api.inquiries.history', $inquiry->id) }}',
            audit: '{{ route('admin.api.inquiries.audit', $inquiry->id) }}',
            addressSuggest: '{{ route('admin.api.address.suggest') }}',
            detailBase: '{{ route('admin.inquiries.show', '__ID__') }}',
            calendarEmbed: '{{ route('admin.calendar.embed') }}',
        },
    })" class="w-full pt-1 pb-24 sm:pb-8">

    {{-- Header: back link + a single Save button (desktop) that appears only when there are unsaved edits --}}
    <div class="sticky top-0 z-30 -mt-1 mb-4 py-2 bg-gray-100/90 backdrop-blur flex items-center justify-between gap-3">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-amber-600 hover:text-amber-700 transition-colors">&larr; Back to list</a>
        <div x-show="dirty" x-cloak class="hidden sm:flex items-center gap-3">
            <span class="text-xs text-amber-600 font-medium">Unsaved changes</span>
            <button type="button" @click="save()" :disabled="saving" class="btn-primary text-sm py-2 px-5"><span x-text="saving ? 'Saving…' : 'Save Changes'"></span></button>
        </div>
    </div>

    {{-- Mobile: floating status bar pinned to the bottom (tap to change status) +
         a Save button that appears when there are unsaved edits --}}
    <div class="sm:hidden fixed inset-x-0 bottom-0 z-40" @click.outside="showStatusSheet = false">
        {{-- status picker sheet (opens upward) --}}
        <div x-show="showStatusSheet" x-cloak x-transition.opacity.duration.150ms
             class="mx-3 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                <span class="text-xs uppercase tracking-widest text-gray-400">Set status</span>
                <button type="button" @click="showStatusSheet = false" class="text-gray-400 p-1 -mr-1"><x-icon name="x" class="w-5 h-5"/></button>
            </div>
            <div class="max-h-[55vh] overflow-y-auto">
                <template x-for="st in statusChoices" :key="st">
                    <button type="button" @click="pickStatus(st)"
                            class="w-full flex items-center gap-3 px-4 py-3.5 text-left border-b border-gray-50 last:border-0 active:bg-gray-100 transition-colors"
                            :class="status === st ? 'bg-amber-50' : ''">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="dotClass(st)"></span>
                        <span class="flex-1 font-medium text-gray-800" x-text="statusLabel(st)"></span>
                        <x-icon name="check" class="w-5 h-5 text-amber-600" x-show="status === st" x-cloak/>
                    </button>
                </template>
            </div>
        </div>

        {{-- bottom bar --}}
        <div class="bg-white border-t border-gray-200 p-2.5 shadow-[0_-2px_12px_rgba(0,0,0,0.12)] flex items-center gap-2">
            <button type="button" @click="showStatusSheet = !showStatusSheet"
                    class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-xl border border-gray-300 bg-gray-50 active:bg-gray-100 transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="dotClass(status)"></span>
                <span class="text-[10px] uppercase tracking-widest text-gray-400 shrink-0">Status</span>
                <span class="flex-1 text-left text-sm font-semibold text-gray-800 truncate" x-text="statusLabel(status)"></span>
                <x-icon name="chevron-down" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" ::class="!showStatusSheet && 'rotate-180'"/>
            </button>
            <button x-show="dirty" x-cloak type="button" @click="save()" :disabled="saving" class="btn-primary py-2.5 px-5 text-sm shrink-0"><span x-text="saving ? '…' : 'Save'"></span></button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

        {{-- Column 1: customer + job details --}}
        <div class="space-y-5">

            {{-- Card 1: Customer --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <span class="font-mono text-amber-700 text-sm tracking-widest bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" x-text="inquiry.ref"></span>
                            <h1 x-show="!isEditingCustomer" class="text-gray-900 text-3xl tracking-widest font-bold" x-text="inquiry.name || '(no name)'"></h1>
                            <div x-show="isEditingCustomer" x-cloak class="grid grid-cols-2 gap-2 mt-1.5">
                                <div><label class="block text-xs font-medium text-gray-500 mb-0.5">First Name</label><input type="text" x-model="firstName" class="input-light text-sm py-1.5 w-full" placeholder="First"></div>
                                <div><label class="block text-xs font-medium text-gray-500 mb-0.5">Last Name</label><input type="text" x-model="lastName" class="input-light text-sm py-1.5 w-full" placeholder="Last"></div>
                            </div>
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
                    {{-- Job type pill (Service is the default) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Job Type</label>
                        <div class="inline-flex rounded-lg border border-gray-300 bg-gray-100 p-0.5">
                            <button type="button" @click="setJobType('service')"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
                                    :class="!isEquipment ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Service</button>
                            <button type="button" @click="setJobType('equipment')"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
                                    :class="isEquipment ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Equipment Rental</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            {{-- Service picker (from the service catalog) --}}
                            <div x-show="!isEquipment">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Service Needed</label>
                                {{-- x-init re-syncs the value after x-for renders the options (x-model
                                     alone binds before they exist, dropping the saved selection). --}}
                                <select x-model="serviceType" @change="onServiceChange()" x-init="$nextTick(() => { $el.value = serviceType })" class="input-light text-sm py-2 w-full">
                                    <option value="">Select a service...</option>
                                    <template x-for="svc in serviceCatalog" :key="svc.id">
                                        <option :value="svc.key" x-text="svc.label"></option>
                                    </template>
                                </select>
                            </div>
                            {{-- Equipment picker (from the equipment catalog) --}}
                            <div x-show="isEquipment" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Equipment Needed</label>
                                <select x-model="equipmentType" x-init="$nextTick(() => { $el.value = equipmentType })" class="input-light text-sm py-2 w-full">
                                    <option value="">Select equipment type...</option>
                                    <template x-for="opt in equipmentOptions" :key="opt.id">
                                        <option :value="opt.name" x-text="opt.name + (opt.avg_cost_per_hour ? ' (~$' + opt.avg_cost_per_hour + '/hr)' : '')"></option>
                                    </template>
                                    <option value="__other__">Other (specify below)</option>
                                </select>
                                <input x-show="equipmentType === '__other__'" type="text" @input="equipmentType = $event.target.value" placeholder="Specify equipment type" class="input-light text-sm py-2 mt-2 w-full" x-cloak>
                            </div>
                            {{-- Customer photo --}}
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

                    {{-- Requested rental duration (equipment mode) --}}
                    <div x-show="isEquipment" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Requested Rental Duration</label>
                        <div class="flex gap-2 max-w-xs">
                            <input type="number" x-model="equipmentRentalDuration" class="input-light text-sm py-2 w-20" placeholder="Qty">
                            <select x-model="equipmentRentalUnit" class="input-light text-sm py-2 flex-1">
                                <option value="">Unit</option><option value="hours">Hours</option><option value="days">Days</option>
                            </select>
                        </div>
                    </div>

                    {{-- Service price (service mode) — catalog price shown as the quote --}}
                    <div x-show="!isEquipment && selectedServicePrice !== null" x-cloak class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-50">
                        <div class="text-xs uppercase tracking-[0.5px] text-emerald-600 font-semibold mb-1">Service Price (catalog)</div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="text-3xl font-black text-emerald-600 tracking-tighter">$<span x-text="money(selectedServicePrice)"></span></div>
                            <button type="button" @click="copyToQuotedPrice(selectedServicePrice)"
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1.5 rounded border border-emerald-500/40 text-emerald-700 hover:bg-emerald-500/10 transition-colors"
                                    title="Copy to the Payment Quoted Price field">
                                <x-icon name="arrow-right" class="w-3 h-3"/> Use as Quoted Price
                            </button>
                        </div>
                        <div class="text-[10px] text-emerald-600/80 mt-1 leading-tight">Default catalog price for this service.</div>
                    </div>

                    {{-- Initial quote block --}}
                    <div x-show="isEquipment && (equipmentType || equipmentRentalDuration || inquiry.initial_estimated_quote)" x-cloak class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-50">
                        <div class="flex items-baseline justify-between mb-1">
                            <div class="text-xs uppercase tracking-[0.5px] text-emerald-600 font-semibold">Initial Quote Presented to Customer</div>
                            <div class="text-[10px] text-emerald-600/70">from quote form</div>
                        </div>
                        <template x-if="inquiry.initial_estimated_quote">
                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="text-3xl font-black text-emerald-600 tracking-tighter">$<span x-text="money(inquiry.initial_estimated_quote)"></span></div>
                                <button type="button" @click="copyToQuotedPrice(inquiry.initial_estimated_quote)"
                                        class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1.5 rounded border border-emerald-500/40 text-emerald-700 hover:bg-emerald-500/10 transition-colors"
                                        title="Copy to the Payment Quoted Price field">
                                    <x-icon name="arrow-right" class="w-3 h-3"/> Use as Quoted Price
                                </button>
                            </div>
                        </template>
                        <template x-if="!inquiry.initial_estimated_quote"><div class="text-sm text-gray-500">No initial quote recorded for this submission</div></template>
                        <div class="text-[10px] text-emerald-600/80 mt-1 leading-tight">This is the exact amount the customer saw and could override on the public request form.</div>
                        <template x-if="currentCatalogInitialQuote !== null">
                            <div class="mt-3 pt-3 border-t border-emerald-500/20 text-xs">
                                <div class="text-emerald-600/70 mb-0.5">Current catalog rates would calculate to:</div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="font-semibold text-emerald-700">$<span x-text="money(currentCatalogInitialQuote)"></span><span x-show="equipmentRentalUnit === 'days'" class="text-[10px] ml-1 text-emerald-600/60">(using today's rates)</span></div>
                                    <button type="button" @click="copyToQuotedPrice(currentCatalogInitialQuote)"
                                            class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded border border-emerald-500/40 text-emerald-700 hover:bg-emerald-500/10 transition-colors"
                                            title="Copy to the Payment Quoted Price field">
                                        <x-icon name="arrow-right" class="w-3 h-3"/> Use as Quoted Price
                                    </button>
                                </div>
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
                            <div class="relative flex-1" @click.outside="addrOpen = false">
                                <input type="text" x-model="address" @input="onAddressInput()"
                                       @focus="addrSuggestions.length && (addrOpen = true)"
                                       @keydown.escape="addrOpen = false" autocomplete="off"
                                       placeholder="123 Main St, Yucaipa..." class="input-light text-sm py-2 w-full">
                                <div x-show="addrLoading" x-cloak class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                                </div>
                                <div x-show="addrOpen && addrSuggestions.length" x-cloak
                                     class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <template x-for="(s, i) in addrSuggestions" :key="i">
                                        <button type="button" @click="pickAddress(s)"
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-amber-50 border-b border-gray-100 last:border-0 flex items-start gap-2">
                                            <x-icon name="map-pin" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500"/>
                                            <span x-text="s.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
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

                    {{-- Day schedule — what's already booked on the selected date --}}
                    <div x-show="datePart(confirmedDateTime)" x-cloak class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="text-xs font-semibold text-gray-700 inline-flex items-center gap-1.5">
                                <x-icon name="calendar" class="w-3.5 h-3.5 text-amber-500"/>
                                <span>Scheduled on <span x-text="new Date(datePart(confirmedDateTime) + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })"></span></span>
                            </div>
                            <button type="button" @click="showCalendarModal = true" class="text-[10px] text-amber-600 hover:text-amber-700 inline-flex items-center gap-0.5 shrink-0">Open day calendar <x-icon name="external-link" class="w-2.5 h-2.5"/></button>
                        </div>

                        {{-- conflict warning --}}
                        <div x-show="dayConflictCount > 0" x-cloak class="mb-2 text-[11px] text-red-700 bg-red-50 border border-red-200 rounded-lg px-2 py-1.5">
                            &#9888;&#65039; <span x-text="dayConflictCount === 1 ? '1 visit overlaps this time slot' : dayConflictCount + ' visits overlap this time slot'"></span>
                        </div>

                        {{-- agenda for the day --}}
                        <div class="space-y-1">
                            <template x-for="ev in daySchedule" :key="ev.id + '-' + ev.start.getTime()">
                                <div class="flex items-start gap-2 px-2 py-1.5 rounded-lg border text-xs transition-colors"
                                     :class="ev.isSelf ? 'border-[#F8C820]/60 bg-[#F8C820]/10' : (ev.conflict ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white')">
                                    <span class="font-mono text-gray-600 shrink-0 whitespace-nowrap pt-0.5" x-text="clock(ev.start) + '–' + clock(ev.end)"></span>
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 mt-1.5" :class="dotClass(ev.status)"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="truncate">
                                            <span class="font-medium text-gray-800" x-text="ev.isSelf ? 'This visit' : (ev.name || '(no name)')"></span>
                                            <span x-show="!ev.isSelf" class="text-gray-400 capitalize" x-text="' · ' + serviceLabel(ev.service_type)"></span>
                                        </div>
                                        <div x-show="ev.address" x-cloak class="mt-0.5 flex items-start gap-1 text-gray-500">
                                            <x-icon name="map-pin" class="w-3 h-3 shrink-0 mt-px text-gray-400"/>
                                            <span class="truncate" x-text="ev.address"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0 pt-0.5">
                                        <span x-show="ev.conflict" x-cloak class="text-[10px] font-semibold text-red-600">conflict</span>
                                        <a x-show="!ev.isSelf" :href="detailUrl(ev.id)" class="text-amber-600 hover:text-amber-700" title="Open quote"><x-icon name="external-link" class="w-3 h-3"/></a>
                                    </div>
                                </div>
                            </template>
                            <div x-show="dayOtherCount === 0" x-cloak class="text-[11px] text-emerald-600 px-1 py-0.5">&check; No other visits scheduled this day.</div>
                        </div>
                    </div>

                    {{-- Day calendar popup — iframe of the selected day's calendar --}}
                    <div x-show="showCalendarModal" x-cloak
                         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                         @click.self="showCalendarModal = false" @keydown.escape.window="showCalendarModal = false">
                        <div class="w-full max-w-3xl bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden flex flex-col" style="height:82vh">
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-200 shrink-0">
                                <div class="text-sm font-semibold text-gray-800">Calendar — <span x-text="confirmedDateTime ? new Date(datePart(confirmedDateTime) + 'T00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) : ''"></span></div>
                                <button type="button" @click="showCalendarModal = false" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="w-5 h-5"/></button>
                            </div>
                            <iframe :src="showCalendarModal ? calendarEmbedUrl : 'about:blank'" class="flex-1 w-full border-0" title="Day calendar"></iframe>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Card 3: Payment --}}
            <div class="card-light border-l-2 border-[#F8C820] p-5">
                <div class="flex items-center gap-3 mb-4"><div class="text-lg font-semibold text-emerald-600">Payment</div><div class="h-px flex-1 bg-gray-200"></div></div>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Quoted Price
                                <span x-show="quoteCopied" x-cloak class="ml-1 text-emerald-600 text-xs font-normal inline-flex items-center gap-0.5"><x-icon name="check" class="w-3 h-3"/> saved</span>
                            </label>
                            <div class="relative"><span class="absolute left-3 top-2.5 text-gray-500">$</span><input type="number" step="0.01" x-model="quotedPrice" placeholder="0.00" class="input-light text-sm py-2 pl-7 w-full"></div>
                            <template x-if="inquiry.initial_estimated_quote">
                                <div class="mt-1 flex items-center gap-2 text-[10px] text-gray-500">
                                    <span>Customer saw: <span class="font-medium text-emerald-600">$<span x-text="money(inquiry.initial_estimated_quote)"></span></span> initially</span>
                                    <button type="button" @click="copyToQuotedPrice(inquiry.initial_estimated_quote)" class="text-emerald-600 hover:text-emerald-700 font-medium underline" title="Copy to Quoted Price">use</button>
                                </div>
                            </template>
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

                    @include('partials.admin.payment-link-panel')
                </div>
            </div>
            </div>{{-- /column 2 --}}

        {{-- Column 3: status timeline, rental agreement, history --}}
        <div class="space-y-4 xl:sticky xl:top-2">
                {{-- Timeline hidden on mobile — the floating bottom bar shows/sets status there --}}
                <div class="hidden sm:block">
                    @include('partials.admin.status-timeline')
                </div>

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
