@extends('layouts.public')

@section('title', 'Get a Free Quote | '.config('business.name'))
@section('description', 'Request a free quote for junk removal or dumpster rental in the Inland Empire. Upload photos for faster, more accurate pricing.')

@section('content')
{{-- Header --}}
<section class="bg-charcoal-800 py-14 text-white">
    <div class="container-wide">
        <nav class="flex items-center gap-2 text-sm text-white/60 mb-5">
            <a href="{{ route('home') }}" class="hover:text-orange-400 transition-colors">Home</a>
            <x-icon name="chevron-right" class="w-4 h-4"/>
            <span class="text-white/80">Get a Quote</span>
        </nav>
        <p class="section-label !text-orange-400">Free Quote</p>
        <h1 class="font-black text-6xl tracking-tighter">Tell us how we can help you</h1>
        <p class="text-xl text-white/70 mt-4 max-w-xl">Fill out the form below. Photos help us give you a better, faster quote. We usually respond the same day.</p>
    </div>
</section>

<section class="py-16 bg-slate-50">
    <div class="container-wide">
        <div class="grid lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div data-reveal="up" class="lg:col-span-3">
                @if($showQuoteForm)
                <div class="card p-8 md:p-10" x-data="quoteForm()">
                    <h2 class="font-black text-2xl mb-8">Request a Quote</h2>

                    {{-- Success --}}
                    <div x-show="status === 'success'" x-cloak class="card border-emerald-200 p-10 text-center bg-emerald-50/50">
                        <x-icon name="check-circle" class="w-14 h-14 text-emerald-500 mx-auto mb-6"/>
                        <h3 class="font-black text-3xl text-slate-900 mb-3">Quote Request Received!</h3>
                        <p x-show="submittedRef" class="text-lg text-emerald-600 font-mono mb-2" x-text="submittedRef"></p>
                        <p class="text-slate-600 mb-8 max-w-sm mx-auto">We'll contact you shortly. You can check the status of your request anytime using your phone number and email.</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('status') }}" class="btn-primary px-8">Check Request Status</a>
                            <a href="tel:{{ config('business.phoneRaw') }}" class="btn-outline px-8"><x-icon name="phone" class="w-4 h-4"/> Call Us</a>
                        </div>
                    </div>

                    {{-- Form --}}
                    <form x-show="status !== 'success'" @submit.prevent="submit()" class="flex flex-col gap-6">
                        {{-- Honeypot --}}
                        <input type="text" x-model="website" tabindex="-1" aria-hidden="true" class="hidden" autocomplete="off">

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Full Name <span class="text-orange-500">*</span></label>
                                <input type="text" placeholder="John Smith" class="input" x-model="name">
                                <p x-show="errors.name" x-text="errors.name" class="text-red-600 text-xs mt-1" x-cloak></p>
                            </div>
                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Phone Number <span class="text-orange-500">*</span></label>
                                <input type="tel" placeholder="(909) 555-1234" class="input" x-model="phone">
                                <p x-show="errors.phone" x-text="errors.phone" class="text-red-600 text-xs mt-1" x-cloak></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">Email Address <span class="text-orange-500">*</span></label>
                            <input type="email" placeholder="you@example.com" class="input" x-model="email">
                            <p x-show="errors.email" x-text="errors.email" class="text-red-600 text-xs mt-1" x-cloak></p>
                        </div>

                        {{-- Preferred contact method + urgency --}}
                        <div class="flex flex-wrap items-start gap-x-8 gap-y-4">
                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Preferred contact method</label>
                                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                                    <button type="button" @click="preferredContactMethod = 'phone'"
                                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors"
                                            :class="preferredContactMethod === 'phone' ? 'bg-[#EAB308] text-charcoal-900' : 'bg-white text-slate-700 hover:bg-gray-50'">
                                        <x-icon name="phone" class="w-4 h-4"/> Phone
                                    </button>
                                    <button type="button" @click="preferredContactMethod = 'email'"
                                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-l border-gray-300 transition-colors"
                                            :class="preferredContactMethod === 'email' ? 'bg-[#EAB308] text-charcoal-900' : 'bg-white text-slate-700 hover:bg-gray-50'">
                                        <x-icon name="mail" class="w-4 h-4"/> Email
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">We'll reach out using your preferred method.</p>
                            </div>

                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Urgency</label>
                                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                                    <button type="button" @click="urgency = 'routine'"
                                            class="px-4 py-2 text-sm font-medium transition-colors"
                                            :class="urgency === 'routine' ? 'bg-[#EAB308] text-charcoal-900' : 'bg-white text-slate-700 hover:bg-gray-50'">
                                        Routine
                                    </button>
                                    <button type="button" @click="urgency = 'urgent'"
                                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-l border-gray-300 transition-colors"
                                            :class="urgency === 'urgent' ? 'bg-red-500 text-white' : 'bg-white text-slate-700 hover:bg-gray-50'">
                                        <x-icon name="alert" class="w-4 h-4"/> Urgent
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Let us know if this is time-sensitive.</p>
                            </div>
                        </div>

                        {{-- Job type pill --}}
                        <div>
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">What do you need? <span class="text-orange-500">*</span></label>
                            <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                                <button type="button" @click="setJobType('service')"
                                        class="px-4 py-2 text-sm font-medium transition-colors"
                                        :class="!isEquipment ? 'bg-[#EAB308] text-charcoal-900' : 'bg-white text-slate-700 hover:bg-gray-50'">Service</button>
                                <button type="button" @click="setJobType('equipment')"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 transition-colors"
                                        :class="isEquipment ? 'bg-[#EAB308] text-charcoal-900' : 'bg-white text-slate-700 hover:bg-gray-50'">Equipment Rental</button>
                            </div>
                        </div>

                        {{-- Service picker (service mode) --}}
                        <div x-show="!isEquipment">
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">Service Needed <span class="text-orange-500">*</span></label>
                            <select class="input" x-model="serviceType">
                                <option value="" disabled>Select a service…</option>
                                <template x-for="opt in serviceChoices" :key="opt.key">
                                    <option :value="opt.key" x-text="opt.label"></option>
                                </template>
                            </select>
                            <p x-show="errors.serviceType" x-text="errors.serviceType" class="text-red-600 text-xs mt-1" x-cloak></p>

                            <div x-show="selectedServicePrice !== null" x-cloak class="mt-4 p-5 bg-gradient-to-r from-emerald-50 to-white border-2 border-emerald-300 rounded-2xl shadow-sm">
                                <div class="text-sm font-semibold text-emerald-700 tracking-wide">ESTIMATED PRICE</div>
                                <div class="text-4xl font-black text-emerald-800 mt-1 tracking-tighter">$<span x-text="money(selectedServicePrice)"></span></div>
                                <div class="text-[10px] text-emerald-600 leading-tight mt-1">Starting estimate — final price confirmed after we review your job.</div>
                            </div>
                        </div>

                        {{-- Equipment picker (equipment mode) --}}
                        <div x-show="isEquipment" x-cloak>
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">Equipment Type <span class="text-orange-500">*</span></label>
                            <select class="input" x-model="selectedEquipment" :disabled="loadingEquipment">
                                <option value="" disabled x-text="loadingEquipment ? 'Loading equipment...' : 'Select equipment...'"></option>
                                <template x-for="opt in equipmentOptions" :key="opt.id">
                                    <option :value="opt.name" x-text="opt.name + (opt.avg_cost_per_hour ? ' — ~$' + opt.avg_cost_per_hour + '/hr' : '')"></option>
                                </template>
                            </select>
                            <p x-show="errors.equipment" x-text="errors.equipment" class="text-red-600 text-xs mt-1" x-cloak></p>
                            <p class="text-xs text-slate-500 mt-1">Prices shown are average hourly rates for reference.</p>

                            <div class="mt-4">
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Rental Duration</label>
                                <div class="flex gap-2 items-end">
                                    <input type="number" min="1" step="1" x-model="equipmentRentalDuration" placeholder="e.g. 4" class="input flex-1">
                                    <select x-model="equipmentRentalUnit" class="input w-28">
                                        <option value="hours">Hours</option>
                                        <option value="days">Days</option>
                                    </select>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">How long do you need the equipment?</p>

                                <div x-show="computedEstimate !== null" x-cloak class="mt-2 text-red-600 font-semibold text-lg">
                                    Initial Quote: $<span x-text="money(computedEstimate)"></span>
                                    <span x-show="equipmentRentalUnit === 'days'"> (based on daily rate)</span>
                                    <span x-show="equipmentRentalUnit === 'hours'"> (based on hourly rate)</span>
                                </div>
                            </div>

                            <div x-show="computedEstimate !== null" x-cloak class="mt-4 p-5 bg-gradient-to-r from-emerald-50 to-white border-2 border-emerald-300 rounded-2xl shadow-sm">
                                <div class="flex items-baseline justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-emerald-700 tracking-wide">ESTIMATED INITIAL QUOTE</div>
                                        <div class="text-4xl font-black text-emerald-800 mt-1 tracking-tighter">$<span x-text="money(computedEstimate)"></span></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] text-emerald-600 leading-tight">Based on catalog rate<br>&times; your duration</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">Description / Notes <span class="text-slate-500 text-xs font-normal">(optional)</span></label>
                            <textarea rows="4" placeholder="Tell us about the items, approximate size, location access, or any other details..." class="input resize-none" x-model="description"></textarea>
                        </div>

                        {{-- Zip + schedule --}}
                        <div class="space-y-5">
                            <div class="sm:max-w-[200px]">
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Zip Code <span class="text-orange-500">*</span></label>
                                <input type="text" placeholder="92399" maxlength="10" class="input" x-model="zipCode">
                                <p x-show="errors.zipCode" x-text="errors.zipCode" class="text-red-600 text-xs mt-1" x-cloak></p>
                            </div>
                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Preferred Day <span class="text-slate-500 text-xs font-normal">(optional)</span></label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="d in [['Mon','Monday'],['Tue','Tuesday'],['Wed','Wednesday'],['Thu','Thursday'],['Fri','Friday']]" :key="d[1]">
                                        <button type="button" @click="togglePref('preferredDay', d[1])"
                                                class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                                                :class="prefHas('preferredDay', d[1]) ? 'bg-orange-500 text-white border-orange-500' : 'bg-white border-gray-300 text-slate-700 hover:border-orange-400'"
                                                x-text="d[0]"></button>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-700 text-sm font-medium mb-1.5">Preferred Time <span class="text-slate-500 text-xs font-normal">(optional)</span></label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="t in [['Morning','Morning (8am - 12pm)'],['Afternoon','Afternoon (12pm - 5pm)'],['Evening','Evening (5pm - 8pm)']]" :key="t[1]">
                                        <button type="button" @click="togglePref('preferredTime', t[1])"
                                                class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                                                :class="prefHas('preferredTime', t[1]) ? 'bg-orange-500 text-white border-orange-500' : 'bg-white border-gray-300 text-slate-700 hover:border-orange-400'"
                                                x-text="t[0]"></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Photo --}}
                        <div>
                            <label class="block text-slate-700 text-sm font-medium mb-1.5">Photo of the junk / job site <span class="text-slate-500 text-xs font-normal">(optional, helps us quote accurately)</span></label>
                            <label x-show="!photo" class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 hover:border-orange-400 rounded-xl p-8 cursor-pointer transition-colors bg-slate-50">
                                <x-icon name="upload" class="w-8 h-8 text-orange-500 mb-3"/>
                                <span class="text-sm text-slate-600">Click to upload a photo (max 5MB)</span>
                                <input type="file" accept="image/*" class="hidden" @change="handlePhoto($event)">
                            </label>
                            <div x-show="photo" x-cloak class="card p-4 flex items-center gap-4">
                                <div class="w-20 h-20 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0 bg-slate-100">
                                    <img :src="photo ? ('data:' + photo.mime + ';base64,' + photo.base64) : ''" alt="Preview" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-800 truncate" x-text="photo?.name"></p>
                                    <p class="text-xs text-slate-500">Photo attached — thank you!</p>
                                </div>
                                <button type="button" @click="removePhoto()" class="text-slate-400 hover:text-red-500 p-2" aria-label="Remove photo">
                                    <x-icon name="x" class="w-5 h-5"/>
                                </button>
                            </div>
                            <p x-show="photoError" x-text="photoError" class="text-red-600 text-xs mt-1" x-cloak></p>
                        </div>

                        {{-- Error --}}
                        <div x-show="status === 'error'" x-cloak class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-lg p-4">
                            <x-icon name="alert" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"/>
                            <p class="text-red-700 text-sm">Something went wrong. Please try again or call us at
                                <a href="tel:{{ config('business.phoneRaw') }}" class="text-orange-600 font-bold">{{ config('business.phone') }}</a>.</p>
                        </div>

                        <button type="submit" :disabled="status === 'loading'" class="btn-primary w-full justify-center py-4 text-base mt-2">
                            <span x-show="status !== 'loading'">Send Quote Request</span>
                            <span x-show="status === 'loading'" x-cloak>Sending Your Request…</span>
                        </button>

                        <p class="text-center text-xs text-slate-500">We respect your time. You'll hear back from us quickly.</p>
                    </form>
                </div>
                @else
                {{-- Quote form hidden via Admin → Site Content --}}
                <div class="card p-8 md:p-10 text-center">
                    <x-icon name="phone" class="w-12 h-12 text-[#EAB308] mx-auto mb-4"/>
                    <h2 class="font-black text-2xl mb-3">Contact Us for a Quote</h2>
                    <p class="text-slate-600 mb-6 max-w-sm mx-auto">Please call or text us and we'll get you a quote right away.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="tel:{{ config('business.phoneRaw') }}" class="btn-primary px-8"><x-icon name="phone" class="w-4 h-4"/> Call {{ config('business.phone') }}</a>
                        <a href="sms:{{ config('business.phoneRaw') }}" class="btn-outline px-8">Text Us</a>
                    </div>
                </div>
                @endif
            </div>

            {{-- Contact info --}}
            <div data-reveal="right" data-reveal-delay="150" class="lg:col-span-2 space-y-8 pt-2">
                <div>
                    <h3 class="font-black text-xl mb-6">Or call us directly</h3>
                    <a href="tel:{{ config('business.phoneRaw') }}" class="flex items-center gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                            <x-icon name="phone" class="w-5 h-5 text-orange-600"/>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Phone</div>
                            <div class="text-2xl font-bold group-hover:text-orange-600 transition-colors">{{ config('business.phone') }}</div>
                        </div>
                    </a>
                </div>
                <div>
                    <a href="mailto:{{ config('business.email') }}" class="flex items-center gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                            <x-icon name="mail" class="w-5 h-5 text-orange-600"/>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Email</div>
                            <div class="font-medium group-hover:text-orange-600 transition-colors">{{ config('business.email') }}</div>
                        </div>
                    </a>
                </div>
                <div class="pt-6 border-t text-sm text-slate-600">
                    <div class="flex items-start gap-3 mb-2">
                        <x-icon name="map-pin" class="w-4 h-4 mt-1 text-orange-500"/>
                        <div>Serving {{ implode(', ', \App\Models\SiteContent::list('serving_areas')) }} and surrounding Inland Empire communities.</div>
                    </div>
                </div>
                <div class="text-xs text-slate-500">Same-day service available in most areas. Call in the morning for afternoon pickup.</div>
            </div>
        </div>
    </div>
</section>
@endsection
