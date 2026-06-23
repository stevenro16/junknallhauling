@extends('layouts.admin')

@section('title', $inquiry->ref.' — '.config('business.name'))

@section('admin-content')
<div x-data="inquiryDetail({
        inquiry: @js($inquiry),
        equipment: @js($equipment),
        services: @js($services),
        allInquiries: @js($allInquiries),
        employees: @js($employees),
        customerPickup: @js($customerPickup),
        scheduleEvents: @js($scheduleEvents),
        history: @js($history),
        urls: {
            update: '{{ route('admin.api.inquiries.update', $inquiry->id) }}',
            history: '{{ route('admin.api.inquiries.history', $inquiry->id) }}',
            audit: '{{ route('admin.api.inquiries.audit', $inquiry->id) }}',
            addressSuggest: '{{ route('admin.api.address.suggest') }}',
            detailBase: '{{ route('admin.inquiries.show', '__ID__') }}',
            calendarEmbed: '{{ route('admin.calendar.embed') }}',
            dashboard: '{{ route('admin.dashboard') }}',
        },
    })" @quote-save.window="dirty && save()"
    @pointermove.window="movePanelDrag($event)" @pointerup.window="endPanelDrag()" @pointercancel.window="endPanelDrag()"
    class="w-full pt-1 pb-24 sm:pb-8">

    {{-- Header: back link + a single Save button (desktop) that appears only when there are unsaved edits --}}
    <div class="sticky top-0 z-30 -mt-1 mb-4 py-2 bg-gray-100/90 backdrop-blur flex items-center justify-between gap-3">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-amber-600 hover:text-amber-700 transition-colors shrink-0">&larr; Back to list</a>
        <div class="flex items-center gap-3 min-w-0">
            <span x-show="saveError" x-cloak class="text-xs text-red-600 font-medium text-right" x-text="saveError"></span>
            <span x-show="dirty && !saveError" x-cloak class="hidden sm:inline text-xs text-amber-600 font-medium">Unsaved changes</span>
            <button type="button" x-show="dirty" x-cloak @click="saveChanges()" :disabled="saving" class="hidden sm:inline-flex btn-primary text-sm py-2 px-5"><span x-text="saving ? 'Saving…' : 'Save Changes'"></span></button>
        </div>
    </div>

    {{-- Mobile: floating status bar pinned to the bottom (tap to change status) +
         a Save button that appears when there are unsaved edits --}}
    <div class="sm:hidden fixed inset-x-0 bottom-14 z-40" @click.outside="showStatusSheet = false; showQuickNav = false; showOtherActions = false">
        {{-- other-actions menu (opens upward): Equipment Delivered / Left Voicemail / Cancel --}}
        <div x-show="showOtherActions" x-cloak x-transition.opacity.duration.150ms
             class="mx-3 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                <span class="text-xs uppercase tracking-widest text-gray-400">Other actions</span>
                <button type="button" @click="showOtherActions = false" class="text-gray-400 p-1 -mr-1"><x-icon name="x" class="w-5 h-5"/></button>
            </div>
            <div class="p-3 space-y-2">
                <button type="button" x-show="isEquipment" @click="showOtherActions = false; quickUpdateStatus('equipment_delivered')" :disabled="saving || status === 'equipment_delivered'"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl border border-cyan-300 bg-cyan-50 text-cyan-700 font-medium text-sm active:bg-cyan-100 disabled:opacity-50 transition-colors">
                    <x-icon name="truck" class="w-5 h-5 shrink-0"/> Equipment Delivered
                </button>
                <button type="button" x-show="status === 'equipment_delivered'" x-cloak @click="showOtherActions = false; quickUpdateStatus('equipment_picked_up')" :disabled="saving"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl border border-sky-300 bg-sky-50 text-sky-700 font-medium text-sm active:bg-sky-100 disabled:opacity-50 transition-colors">
                    <x-icon name="check-circle" class="w-5 h-5 shrink-0"/> Equipment Picked Up
                </button>
                <button type="button" @click="showOtherActions = false; quickUpdateStatus('left_voicemail')" :disabled="saving || status === 'left_voicemail'"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl border border-yellow-300 bg-yellow-50 text-yellow-700 font-medium text-sm active:bg-yellow-100 disabled:opacity-50 transition-colors">
                    <x-icon name="voicemail" class="w-5 h-5 shrink-0"/> Left Voicemail
                </button>
                <button type="button" @click="showOtherActions = false; quickUpdateStatus('cancelled')" :disabled="saving || status === 'cancelled'"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl border border-red-300 bg-red-50 text-red-700 font-medium text-sm active:bg-red-100 disabled:opacity-50 transition-colors">
                    <x-icon name="x" class="w-5 h-5 shrink-0"/> Cancel Quote
                </button>
            </div>
        </div>

        {{-- quick jump-to-section menu (opens upward) --}}
        <div x-show="showQuickNav" x-cloak x-transition.opacity.duration.150ms
             class="mx-3 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                <span class="text-xs uppercase tracking-widest text-gray-400">Jump to section</span>
                <button type="button" @click="showQuickNav = false" class="text-gray-400 p-1 -mr-1"><x-icon name="x" class="w-5 h-5"/></button>
            </div>
            <div class="grid grid-cols-2 gap-2 p-3">
                <button type="button" @click="scrollToSection('sec-customer')" class="flex items-center gap-2 px-3 py-3 rounded-xl border transition-colors" :class="sectionDone.customer ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500'">
                    <x-icon name="user" class="w-4 h-4 shrink-0"/><span class="text-sm font-medium flex-1 text-left">Customer</span><x-icon name="check-circle" class="w-4 h-4 shrink-0" x-show="sectionDone.customer" x-cloak/>
                </button>
                <button type="button" @click="scrollToSection('sec-job')" class="flex items-center gap-2 px-3 py-3 rounded-xl border transition-colors" :class="sectionDone.job ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500'">
                    <x-icon name="package" class="w-4 h-4 shrink-0"/><span class="text-sm font-medium flex-1 text-left">Job</span><x-icon name="check-circle" class="w-4 h-4 shrink-0" x-show="sectionDone.job" x-cloak/>
                </button>
                <button type="button" @click="scrollToSection('sec-visit')" class="flex items-center gap-2 px-3 py-3 rounded-xl border transition-colors" :class="sectionDone.visit ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500'">
                    <x-icon name="calendar" class="w-4 h-4 shrink-0"/><span class="text-sm font-medium flex-1 text-left">Visit</span><x-icon name="check-circle" class="w-4 h-4 shrink-0" x-show="sectionDone.visit" x-cloak/>
                </button>
                <button type="button" @click="scrollToSection('sec-payment')" class="flex items-center gap-2 px-3 py-3 rounded-xl border transition-colors" :class="sectionDone.payment ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500'">
                    <x-icon name="dollar-sign" class="w-4 h-4 shrink-0"/><span class="text-sm font-medium flex-1 text-left">Payment</span><x-icon name="check-circle" class="w-4 h-4 shrink-0" x-show="sectionDone.payment" x-cloak/>
                </button>
            </div>
            <p class="px-3 pb-3 -mt-1 text-[10px] text-gray-400">Green when a section is complete.</p>
        </div>

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
            <button type="button" @click="showOtherActions = !showOtherActions; showStatusSheet = false; showQuickNav = false"
                    class="shrink-0 w-11 h-11 flex items-center justify-center rounded-xl border transition-colors"
                    :class="showOtherActions ? 'border-amber-300 bg-amber-50 text-amber-600' : 'border-gray-300 bg-gray-50 text-gray-600 active:bg-gray-100'"
                    aria-label="Other actions">
                <x-icon name="settings" class="w-5 h-5"/>
            </button>
            <button type="button" @click="showStatusSheet = !showStatusSheet; showQuickNav = false; showOtherActions = false"
                    class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-xl border border-gray-300 bg-gray-50 active:bg-gray-100 transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="dotClass(status)"></span>
                <span class="text-[10px] uppercase tracking-widest text-gray-400 shrink-0">Status</span>
                <span class="flex-1 text-left text-sm font-semibold text-gray-800 truncate" x-text="statusLabel(status)"></span>
                <x-icon name="chevron-down" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" ::class="!showStatusSheet && 'rotate-180'"/>
            </button>
            <button x-show="dirty" x-cloak type="button" @click="saveChanges()" :disabled="saving" class="btn-primary py-2.5 px-5 text-sm shrink-0"><span x-text="saving ? '…' : 'Save'"></span></button>
            <button type="button" @click="showQuickNav = !showQuickNav; showStatusSheet = false; showOtherActions = false"
                    class="shrink-0 w-11 h-11 flex items-center justify-center rounded-xl border transition-colors"
                    :class="showQuickNav ? 'border-amber-300 bg-amber-50 text-amber-600' : 'border-gray-300 bg-gray-50 text-gray-600 active:bg-gray-100'"
                    aria-label="Jump to section">
                <x-icon name="file-text" class="w-5 h-5"/>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

        {{-- Column 1: customer + job details --}}
        <div class="space-y-5">

            {{-- Card 1: Customer --}}
            <div id="sec-customer" class="card-light border-l-2 p-5 scroll-mt-20" :class="sectionDone.customer ? 'border-emerald-400' : 'border-[#F8C820]'">
                <div class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <span class="font-mono text-amber-700 text-sm tracking-widest bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" x-text="inquiry.ref"></span>
                            <h1 x-show="!isEditingCustomer" class="text-gray-900 text-3xl tracking-widest font-bold" x-text="inquiry.name || '(no name)'"></h1>
                        </div>
                        <div class="flex items-center gap-2">
                            <span x-show="urgency === 'urgent'" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-300"><x-icon name="alert" class="w-3 h-3"/> Urgent</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusClass(status)" x-text="statusLabel(status)"></span>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" @click="togglePreferredContact()" class="p-1.5 rounded-full border border-gray-300 hover:border-amber-400 text-amber-600 hover:text-amber-700 transition-all active:scale-95" title="Toggle preferred contact">
                                    <x-icon name="phone" class="w-4 h-4" x-show="preferredContactMethod === 'phone'"/>
                                    <x-icon name="mail" class="w-4 h-4" x-show="preferredContactMethod === 'email'" x-cloak/>
                                </button>
                                {{-- Section-complete check + collapse toggle, to the right of the preference icon --}}
                                <x-icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" x-show="sectionDone.customer" x-cloak/>
                                <button type="button" @click="toggleSection('customer')" class="p-1.5 text-gray-400 hover:text-gray-600 shrink-0" x-show="isMobile" x-cloak title="Collapse / expand section">
                                    <x-icon name="chevron-down" class="w-5 h-5 transition-transform" ::class="!collapsed.customer && 'rotate-180'"/>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div x-show="sectionOpen('customer')" x-cloak class="flex flex-col gap-3">

                    <div x-show="isEditingCustomer" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name</label><input type="text" x-model="firstName" class="input-light text-sm py-1.5 w-full" placeholder="First"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><input type="text" x-model="lastName" class="input-light text-sm py-1.5 w-full" placeholder="Last"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label><input type="tel" x-model="phone" @input="saveError = ''" class="input-light text-sm py-1.5 w-full" placeholder="Phone number"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" x-model="email" class="input-light text-sm py-1.5 w-full" placeholder="Email address"></div>
                    </div>

                    {{-- Urgency --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Urgency</label>
                        <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                            <button type="button" @click="urgency = 'routine'" class="px-3 py-1.5 transition-colors" :class="urgency === 'routine' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">Routine</button>
                            <button type="button" @click="urgency = 'urgent'" class="px-3 py-1.5 border-l border-gray-300 transition-colors inline-flex items-center gap-1" :class="urgency === 'urgent' ? 'bg-red-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"><x-icon name="alert" class="w-3.5 h-3.5"/> Urgent</button>
                        </div>
                    </div>

                    {{-- A prior order shares this phone/email — offer to pull that customer's saved info in. --}}
                    <template x-if="customerMatch">
                        <div class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3">
                            <x-icon name="user" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"/>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-amber-900">Previous order for this customer</div>
                                <div class="text-xs text-amber-800 mt-0.5 truncate">
                                    <span class="font-semibold" x-text="customerMatch.name || 'Unnamed'"></span><template x-if="customerMatch.ref"><span> · <span class="font-mono" x-text="customerMatch.ref"></span></span></template><span> · <span x-text="date(customerMatch.created_at)"></span></span>
                                </div>
                            </div>
                            <button type="button" @click="pullCustomerInfo()"
                                    class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-md text-white"
                                    :class="customerPulled ? 'bg-emerald-500' : 'bg-amber-500 hover:bg-amber-600'"
                                    x-text="customerPulled ? 'Pulled ✓' : 'Use this info'"></button>
                        </div>
                    </template>

                    <template x-if="isEditingCustomer">
                        <div x-data="{ open: false }" class="rounded-xl border border-gray-200">
                            <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 text-left">
                                <span class="text-sm font-semibold text-gray-700">Additional Details</span>
                                <x-icon name="chevron-down" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" ::class="open && 'rotate-180'"/>
                            </button>
                            <div x-show="open" x-cloak class="space-y-4 px-3 pb-3 pt-3 border-t border-gray-200">
                                <div class="sm:max-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label><input type="text" x-model="customerZip" class="input-light text-sm py-1.5 w-full" placeholder="Zip code"></div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Day</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="d in [['Mon','Monday'],['Tue','Tuesday'],['Wed','Wednesday'],['Thu','Thursday'],['Fri','Friday']]" :key="d[1]">
                                            <button type="button" @click="togglePref('customerPreferredDay', d[1])"
                                                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
                                                    :class="prefHas('customerPreferredDay', d[1]) ? 'bg-amber-500 text-white border-amber-500' : 'bg-white border-gray-300 text-gray-700 hover:border-amber-400 hover:bg-amber-50'"
                                                    x-text="d[0]"></button>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="t in [['Morning','Morning (8am - 12pm)'],['Afternoon','Afternoon (12pm - 5pm)'],['Evening','Evening (5pm - 8pm)']]" :key="t[1]">
                                            <button type="button" @click="togglePref('customerPreferredTime', t[1])"
                                                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
                                                    :class="prefHas('customerPreferredTime', t[1]) ? 'bg-amber-500 text-white border-amber-500' : 'bg-white border-gray-300 text-gray-700 hover:border-amber-400 hover:bg-amber-50'"
                                                    x-text="t[0]"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
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
            </div>

            {{-- Card 2: Job Details --}}
            <div id="sec-job" class="card-light border-l-2 border-[#F8C820] p-5 scroll-mt-20">
                <button type="button" @click="toggleSection('job')" class="w-full flex items-center gap-3 mb-4 text-left">
                    <div class="text-lg font-semibold transition-colors" :class="sectionDone.job ? 'text-emerald-600' : 'text-amber-700'">Job Details</div>
                    <x-icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" x-show="sectionDone.job" x-cloak/>
                    <div class="h-px flex-1" :class="sectionDone.job ? 'bg-emerald-200' : 'bg-gray-200'"></div>
                    <x-icon name="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" ::class="!collapsed.job && 'rotate-180'" x-show="isMobile" x-cloak/>
                </button>
                <div x-show="sectionOpen('job')" x-cloak class="space-y-3">
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
                        <p x-show="jobError" x-cloak class="text-xs text-red-600 mt-1.5">Please select a <span x-text="isEquipment ? 'equipment type' : 'service'"></span> before saving.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            {{-- Service picker (from the service catalog) --}}
                            <div x-show="!isEquipment">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Service Needed <span class="text-red-500">*</span></label>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Equipment Needed <span class="text-red-500">*</span></label>
                                <select x-model="equipmentType" @change="jobError = false; saveError = ''" x-init="$nextTick(() => { $el.value = equipmentType })" class="input-light text-sm py-2 w-full">
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

                    {{-- Rental duration (equipment mode) — how long the customer keeps the equipment --}}
                    <div x-show="isEquipment" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Rental Duration</label>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="number" x-model="equipmentRentalDuration" class="input-light text-sm py-2 w-20" placeholder="Qty">
                            {{-- Hours / Days pill toggle (Hours is the default) --}}
                            <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                                <button type="button" @click="equipmentRentalUnit = 'hours'" class="px-4 py-1.5 transition-colors" :class="equipmentRentalUnit === 'days' ? 'bg-white text-gray-600 hover:bg-gray-50' : 'bg-amber-500 text-white'">Hours</button>
                                <button type="button" @click="equipmentRentalUnit = 'days'" class="px-4 py-1.5 border-l border-gray-300 transition-colors" :class="equipmentRentalUnit === 'days' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">Days</button>
                            </div>
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
                        <div x-show="currentCatalogDescription" x-cloak class="flex justify-end mt-1.5">
                            <button type="button" @click="pullCatalogDescription()"
                                    class="text-xs font-medium text-amber-600 hover:text-amber-700 inline-flex items-center gap-1">
                                <x-icon name="plus" class="w-3.5 h-3.5"/>
                                Add <span x-text="isEquipment ? 'equipment' : 'service'"></span> description
                            </button>
                        </div>
                    </div>

                    {{-- Rental agreement (equipment rentals only) — condensed generate/send + signed view --}}
                    <template x-if="isEquipment">
                        @include('partials.admin.rental-agreement-panel')
                    </template>

                </div>
            </div>

            </div>{{-- /column 1 --}}

            {{-- Column 2: scheduling + payment --}}
            <div class="space-y-5">
            {{-- Card: Visit Date & Time --}}
            <div id="sec-visit" class="card-light border-l-2 border-[#F8C820] p-5 scroll-mt-20">
                <button type="button" @click="toggleSection('visit')" class="w-full flex items-center gap-3 mb-4 text-left">
                    <div class="text-lg font-semibold transition-colors" :class="sectionDone.visit ? 'text-emerald-600' : 'text-amber-700'">Visit Date &amp; Time</div>
                    <x-icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" x-show="sectionDone.visit" x-cloak/>
                    <div class="h-px flex-1" :class="sectionDone.visit ? 'bg-emerald-200' : 'bg-gray-200'"></div>
                    <x-icon name="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" ::class="!collapsed.visit && 'rotate-180'" x-show="isMobile" x-cloak/>
                </button>
                <div x-show="sectionOpen('visit')" x-cloak class="space-y-3">
                    {{-- Assigned employee --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned To</label>
                        @include('partials.admin.assignee-picker', ['model' => 'assignedEmployeeIds'])
                        @if(count($employees) <= 1)
                            <p class="text-[10px] text-gray-400 mt-1">No employee accounts yet — create one in Account Management, or assign yourself.</p>
                        @endif
                    </div>

                    {{-- Date / Time / Duration --}}
                    <div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Date</div>
                                <input type="date" :value="datePart(confirmedDateTime)" @change="setConfirmedDate($event.target.value)" class="input-light text-sm py-2 w-full">
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Time</div>
                                {{-- x-init re-syncs the value after x-for renders the slots (a saved time
                                     would otherwise not match before the options exist). --}}
                                <select :value="timePart(confirmedDateTime)" @change="setConfirmedTime($event.target.value)" x-init="$nextTick(() => { $el.value = timePart(confirmedDateTime) })" class="input-light text-sm py-2 w-full">
                                    <option value="">Select time...</option>
                                    <template x-for="slot in TIME_SLOTS" :key="slot"><option :value="slot" x-text="fmtTime12(slot)"></option></template>
                                </select>
                            </div>
                        </div>

                        {{-- Customer preferences + the next dates that match their preferred day(s) --}}
                        <template x-if="customerPreferredDay || customerPreferredTime">
                            <div class="mt-2">
                                <div class="text-[11px] text-gray-500 mb-1">Customer prefers: <span class="font-medium text-gray-600" x-text="customerPreferredDay || 'any day'"></span><span x-show="customerPreferredTime"> · <span class="font-medium text-gray-600" x-text="customerPreferredTime"></span></span></div>
                                <div x-show="recommendedDates(3).length" class="flex gap-2 flex-wrap">
                                    <template x-for="(dateStr, index) in recommendedDates(3)" :key="index">
                                        <button type="button" @click="pickPreferredDate(dateStr)" class="text-xs px-2.5 py-1 rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 active:scale-[0.985] transition-all" x-text="dayLabel(dateStr)"></button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="mt-3">
                            <div class="text-sm font-medium text-gray-700 mb-1.5">Visit Duration <span class="font-normal text-xs text-gray-400">— employee's time on site</span></div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="stepDuration(-1)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">&minus;</button>
                                <input type="number" x-model="expectedDurationValue" class="input-light text-sm py-2 flex-1 min-w-0 text-center px-1" placeholder="—">
                                <select x-model="expectedDurationUnit" class="input-light text-sm py-2 w-20 px-1 shrink-0"><option value="hours">hrs</option><option value="days">days</option></select>
                                <button type="button" @click="stepDuration(1)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">+</button>
                            </div>
                        </div>

                    </div>

                    {{-- Visit day schedule — moved directly under the visit duration --}}
                    @include('partials.admin.day-schedule-panel', [
                        'dateExpr' => 'confirmedDateTime', 'columns' => 'dayScheduleColumns',
                        'conflict' => 'dayConflictCount', 'other' => 'dayOtherCount',
                        'modal' => 'showCalendarModal', 'selfLabel' => 'This visit',
                        'kind' => 'visit',
                    ])

                    {{-- Equipment Pickup (equipment rentals) — own date/time + duration + day calendar --}}
                    <template x-if="isEquipment">
                        <div class="pt-3 border-t border-gray-200 space-y-3">
                            <div class="text-sm font-semibold text-cyan-700 inline-flex items-center gap-1.5"><x-icon name="truck" class="w-4 h-4"/> Equipment Pickup</div>

                            {{-- Warn if the pickup is scheduled before the delivery visit --}}
                            <div x-show="pickupBeforeVisit" x-cloak class="flex items-start gap-2 rounded-lg border border-red-300 bg-red-50 p-2.5 text-xs text-red-700">
                                <x-icon name="alert" class="w-4 h-4 shrink-0 mt-px"/>
                                <span>The pickup is scheduled <span class="font-semibold">before</span> the delivery visit. Double-check the dates.</span>
                            </div>

                            {{-- Customer-requested pickup from a signed agreement (only when we haven't scheduled one) --}}
                            <template x-if="showCustomerPickup">
                                <div class="rounded-lg border border-cyan-300 bg-cyan-50 p-3">
                                    <div class="text-xs font-semibold text-cyan-800">Customer requested a pickup</div>
                                    <div class="text-sm text-gray-800 mt-0.5" x-text="customerPickupLabel"></div>
                                    <button type="button" @click="applyCustomerPickup()" class="mt-2 text-xs font-semibold px-3 py-1.5 rounded-md bg-cyan-600 text-white hover:bg-cyan-700">Use this for pickup</button>
                                </div>
                            </template>

                            {{-- Estimated pickup from the visit time + rental duration (when nothing is set / requested) --}}
                            <template x-if="showEstimatedPickup">
                                <div class="rounded-lg border border-cyan-200 bg-cyan-50/60 p-3">
                                    <div class="text-xs font-semibold text-cyan-800">Estimated pickup</div>
                                    <div class="text-sm text-gray-800 mt-0.5" x-text="estimatedPickup.label"></div>
                                    <div class="text-[10px] text-gray-500 mt-0.5">Based on the visit time + rental duration (counts business hours 7am&ndash;5pm).</div>
                                    <button type="button" @click="applyEstimatedPickup()" class="mt-2 text-xs font-semibold px-3 py-1.5 rounded-md bg-cyan-600 text-white hover:bg-cyan-700">Use estimate</button>
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-700 mb-1.5">Pickup Date</div>
                                    <input type="date" :value="datePart(pickupDateTime)" @change="setPickupDate($event.target.value)" class="input-light text-sm py-2 w-full">
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-700 mb-1.5">Pickup Time</div>
                                    <select :value="timePart(pickupDateTime)" @change="setPickupTime($event.target.value)" x-init="$nextTick(() => { $el.value = timePart(pickupDateTime) })" class="input-light text-sm py-2 w-full">
                                        <option value="">Select time...</option>
                                        <template x-for="slot in TIME_SLOTS" :key="slot"><option :value="slot" x-text="fmtTime12(slot)"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Pickup Duration</div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="stepPickupDuration(-1)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">&minus;</button>
                                    <input type="number" x-model="pickupDurationValue" class="input-light text-sm py-2 flex-1 min-w-0 text-center px-1" placeholder="—">
                                    <select x-model="pickupDurationUnit" class="input-light text-sm py-2 w-20 px-1 shrink-0"><option value="hours">hrs</option><option value="days">days</option></select>
                                    <button type="button" @click="stepPickupDuration(1)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-base font-medium shrink-0">+</button>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1.5">Pickup Assigned To</div>
                                @include('partials.admin.assignee-picker', ['model' => 'pickupAssignedEmployeeIds'])
                            </div>

                            {{-- Pickup day schedule --}}
                            @include('partials.admin.day-schedule-panel', [
                                'dateExpr' => 'pickupDateTime', 'columns' => 'pickupDayScheduleColumns',
                                'conflict' => 'pickupDayConflictCount', 'other' => 'pickupDayOtherCount',
                                'modal' => 'showPickupCalendarModal', 'selfLabel' => 'This pickup',
                                'iconColor' => 'text-cyan-600', 'kind' => 'pickup',
                            ])
                        </div>
                    </template>

                    {{-- Move to Scheduled + optional customer notification (once a date & time are set) --}}
                    <template x-if="canSchedule">
                        <div class="rounded-xl border border-[#F8C820]/60 bg-amber-50/60 p-3">
                            <div class="flex items-start gap-2">
                                <x-icon name="calendar" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"/>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-amber-900">Mark this quote as Scheduled?</div>
                                    <div class="text-xs text-amber-800/90 mt-0.5" x-text="scheduledSummary"></div>
                                </div>
                            </div>
                            <label class="mt-2.5 flex items-start gap-2 text-xs text-gray-700 cursor-pointer select-none">
                                <input type="checkbox" x-model="notifyCustomer" class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                <span>Notify the customer of the visit via their preferred method (<span class="font-medium" x-text="preferredMethodLabel"></span>)</span>
                            </label>
                            <button type="button" @click="markScheduled()" :disabled="saving" class="mt-3 w-full btn-primary py-2 text-sm">
                                <span x-text="saving ? 'Saving…' : (notifyCustomer ? 'Schedule &amp; Notify Customer' : 'Mark as Scheduled')"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Already scheduled — offer to (re)send the visit confirmation --}}
                    <template x-if="hasConfirmedSlot && status === 'scheduled'">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 flex items-center justify-between gap-2">
                            <div class="text-xs text-emerald-700 inline-flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4"/> Scheduled</div>
                            <button type="button" @click="notifyCustomerOfVisit()" class="text-xs font-medium text-amber-600 hover:text-amber-700 inline-flex items-center gap-1">
                                Send visit confirmation (<span x-text="preferredMethodLabel"></span>)
                            </button>
                        </div>
                    </template>

                    {{-- Day calendar popup — iframe of the selected day's calendar --}}
                    <div x-show="showCalendarModal" x-cloak
                         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                         @click.self="showCalendarModal = false" @keydown.escape.window="showCalendarModal = false">
                        <div class="w-full max-w-3xl bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden flex flex-col" style="height:82vh">
                            <div class="flex items-start justify-between px-4 py-2.5 border-b border-gray-200 shrink-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-800">Calendar — <span x-text="confirmedDateTime ? new Date(datePart(confirmedDateTime) + 'T00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) : ''"></span></div>
                                    <div x-show="customerPreferredDay || customerPreferredTime" x-cloak class="mt-0.5 text-[11px] text-amber-700 inline-flex items-center gap-1">
                                        <x-icon name="user" class="w-3 h-3 shrink-0 text-amber-500"/>
                                        <span>Customer prefers: <span class="font-medium" x-text="customerPreferredDay || 'any day'"></span><span x-show="customerPreferredTime"> · <span class="font-medium" x-text="customerPreferredTime"></span></span></span>
                                    </div>
                                </div>
                                <button type="button" @click="showCalendarModal = false" class="text-gray-400 hover:text-gray-600 shrink-0"><x-icon name="x" class="w-5 h-5"/></button>
                            </div>
                            <iframe :src="showCalendarModal ? calendarEmbedUrl : 'about:blank'" class="flex-1 w-full border-0" title="Day calendar"></iframe>
                        </div>
                    </div>

                    {{-- Pickup day calendar popup --}}
                    <div x-show="showPickupCalendarModal" x-cloak
                         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                         @click.self="showPickupCalendarModal = false" @keydown.escape.window="showPickupCalendarModal = false">
                        <div class="w-full max-w-3xl bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden flex flex-col" style="height:82vh">
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-200 shrink-0">
                                <div class="text-sm font-semibold text-gray-800">Pickup Calendar — <span x-text="pickupDateTime ? new Date(datePart(pickupDateTime) + 'T00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) : ''"></span></div>
                                <button type="button" @click="showPickupCalendarModal = false" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="w-5 h-5"/></button>
                            </div>
                            <iframe :src="showPickupCalendarModal ? pickupCalendarEmbedUrl : 'about:blank'" class="flex-1 w-full border-0" title="Pickup day calendar"></iframe>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Card 3: Payment --}}
            <div id="sec-payment" class="card-light border-l-2 border-[#F8C820] p-5 scroll-mt-20">
                <button type="button" @click="toggleSection('payment')" class="w-full flex items-center gap-3 mb-4 text-left">
                    <div class="text-lg font-semibold transition-colors" :class="sectionDone.payment ? 'text-emerald-600' : 'text-amber-700'">Payment</div>
                    <x-icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" x-show="sectionDone.payment" x-cloak/>
                    <div class="h-px flex-1" :class="sectionDone.payment ? 'bg-emerald-200' : 'bg-gray-200'"></div>
                    <x-icon name="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" ::class="!collapsed.payment && 'rotate-180'" x-show="isMobile" x-cloak/>
                </button>
                <div x-show="sectionOpen('payment')" x-cloak class="space-y-3">
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

        {{-- Column 3: status timeline, notes, history --}}
        <div class="space-y-4 xl:sticky xl:top-2">
                {{-- Timeline hidden on mobile — the floating bottom bar shows/sets status there --}}
                <div class="hidden sm:block">
                    @include('partials.admin.status-timeline')
                </div>

                {{-- Notes & comments (internal + customer-visible) --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    @include('partials.admin.comment-thread', ['postUrl' => route('admin.api.inquiries.comment', $inquiry->id), 'comments' => $comments])
                </div>

                {{-- Service Visit — field record captured by the assigned employee (read-only) --}}
                @if($inquiry->arrived_at || $inquiry->departed_at || $inquiry->service_signature)
                    <div class="bg-white border border-gray-200 rounded-xl p-4">
                        <div class="text-sm font-medium text-gray-700 mb-3">Service Visit</div>
                        <dl class="text-sm space-y-2">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Arrived</dt>
                                <dd class="text-gray-900 text-right">{{ $inquiry->arrived_at ? $inquiry->arrived_at->format('D, M j · g:i A') : '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Departed</dt>
                                <dd class="text-gray-900 text-right">{{ $inquiry->departed_at ? $inquiry->departed_at->format('D, M j · g:i A') : '—' }}</dd>
                            </div>
                        </dl>
                        @if($inquiry->service_signature)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="text-xs text-gray-500 mb-1">Customer signature
                                    @if($inquiry->service_signed_at)<span class="text-gray-400">· {{ $inquiry->service_signed_at->format('M j, g:i A') }}</span>@endif
                                </div>
                                <img src="{{ $inquiry->service_signature }}" alt="Customer signature" class="border border-gray-200 rounded-lg bg-white max-h-28">
                            </div>
                        @endif
                    </div>
                @endif

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
