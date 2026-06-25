@extends('layouts.public')

@section('title', 'Confirm Your Service Details | '.config('business.name'))

@section('content')
<div x-data="quoteDetailsForm('{{ $token }}')">
    {{-- Loading --}}
    <div x-show="loading" class="min-h-screen flex items-center justify-center bg-[#F8F7F4]">
        <x-icon name="circle" class="w-8 h-8 animate-spin text-[#EAB308]"/>
    </div>

    {{-- Error (no data) --}}
    <div x-show="!loading && error && !data" x-cloak class="min-h-screen flex items-center justify-center bg-[#F8F7F4] p-6">
        <div class="max-w-md text-center">
            <div class="text-4xl mb-4">&#9888;&#65039;</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Link Problem</h1>
            <p class="text-gray-600" x-text="error"></p>
            <p class="text-sm text-gray-500 mt-4">Please contact {{ config('business.name') }} directly if you need a new link.</p>
        </div>
    </div>

    {{-- Submitted --}}
    <div x-show="submitted" x-cloak class="bg-[#F8F7F4] flex justify-center px-4 py-12">
        <div class="max-w-lg w-full bg-white rounded-2xl shadow p-8 text-center">
            <x-icon name="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"/>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 mb-2">Thank You!</h1>
            <p class="text-gray-600 mb-6">Your details have been submitted for quote
                <span class="font-mono text-[#EAB308]" x-text="inquiry?.ref"></span>. We'll review everything and confirm your appointment shortly.</p>
            <p class="text-sm text-gray-500">If you have any questions, call or text us at the number on your original quote.</p>
        </div>
    </div>

    {{-- Form --}}
    <div x-show="!loading && data && !submitted" x-cloak class="min-h-screen bg-[#F8F7F4] py-10 px-4">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-6">
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-gray-900">Confirm Your Service Details</h1>
                <p class="text-gray-700 mt-2 text-sm md:text-base">Please review and complete your details below, confirm your appointment, and sign.</p>
                <p class="text-xs text-gray-500 mt-1">Quote <span class="font-mono font-medium text-[#EAB308]" x-text="inquiry?.ref"></span></p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 md:p-8">
                <form @submit.prevent="submit()" novalidate class="space-y-8">
                    {{-- Your Information --}}
                    <div x-ref="nameField" class="rounded-xl transition-shadow" :class="invalidField === 'nameField' && 'ring-2 ring-red-400 p-3 -m-3'">
                        <h2 class="font-semibold text-lg mb-4 text-gray-800">Your Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="firstName" class="input-dark w-full" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="lastName" class="input-dark w-full" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" :value="inquiry?.phone" disabled class="input-dark w-full bg-gray-100 text-gray-500 cursor-not-allowed">
                                <p class="text-[10px] text-gray-400 mt-1">To change your number, please call us.</p>
                            </div>
                            <div x-ref="emailField" :class="invalidField === 'emailField' && 'ring-2 ring-red-400 rounded-lg p-2 -m-2'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span x-show="preferredContactMethod === 'email'" x-cloak class="text-red-500">*</span></label>
                                <input type="email" x-model="email" class="input-dark w-full" placeholder="you@example.com">
                            </div>
                            <div class="md:col-span-2" x-ref="addressField" :class="invalidField === 'addressField' && 'ring-2 ring-red-400 rounded-lg p-2 -m-2'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Street Address <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addressStreet" class="input-dark w-full" placeholder="123 Main St" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addressCity" class="input-dark w-full" placeholder="City" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <input type="text" x-model="addressState" class="input-dark w-full" placeholder="CA">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Zip <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="zipCode" class="input-dark w-full" placeholder="Zip code" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Preferred contact method (email becomes required when chosen) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Contact Method</label>
                        <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                            <button type="button" @click="preferredContactMethod = 'phone'" class="px-4 py-1.5 transition-colors" :class="preferredContactMethod === 'phone' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">Text / Call</button>
                            <button type="button" @click="preferredContactMethod = 'email'" class="px-4 py-1.5 border-l border-gray-300 transition-colors" :class="preferredContactMethod === 'email' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">Email</button>
                        </div>
                    </div>

                    {{-- Photos (optional, up to 2) --}}
                    <div>
                        <h2 class="font-semibold text-lg mb-1 text-gray-800">Photos <span class="text-sm font-normal text-gray-400">(optional)</span></h2>
                        <p class="text-xs text-gray-500 mb-3">Add up to 2 photos of the items or area (10MB each) to help us prepare.</p>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="(p, i) in photos" :key="i">
                                <div class="relative">
                                    <img :src="p.url" alt="Uploaded photo" class="w-24 h-24 object-cover rounded-lg border border-gray-200">
                                    <button type="button" @click="removePhoto(i)" class="absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center rounded-full bg-red-500 text-white shadow hover:bg-red-600" title="Remove"><x-icon name="x" class="w-3.5 h-3.5"/></button>
                                </div>
                            </template>
                            <label x-show="photos.length < 2" class="w-24 h-24 flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 text-gray-400 cursor-pointer hover:border-amber-400 hover:text-amber-500 transition-colors">
                                <x-icon name="upload" class="w-5 h-5"/>
                                <span class="text-[10px] font-medium">Add photo</span>
                                <input type="file" accept="image/*" multiple class="hidden" @change="addPhotos($event)">
                            </label>
                        </div>
                        <p x-show="photoError" x-text="photoError" x-cloak class="text-red-500 text-xs mt-2"></p>
                    </div>

                    {{-- Please confirm: scheduled date/time + quoted amount (highlighted — important) --}}
                    <div x-ref="confirmField" class="rounded-2xl border-2 border-emerald-400 bg-emerald-50 p-5 space-y-4 shadow-sm" :class="invalidField === 'confirmField' && 'ring-2 ring-red-400'">
                        <h2 class="font-bold text-lg text-emerald-800 inline-flex items-center gap-2"><x-icon name="check-circle" class="w-5 h-5 text-emerald-600"/> Please Confirm</h2>

                        <label class="flex items-start gap-3 cursor-pointer rounded-lg bg-white/70 border border-emerald-200 p-3">
                            <input type="checkbox" x-model="confirmDatetime" class="mt-1 w-5 h-5 accent-emerald-600">
                            <span class="text-sm text-gray-700">
                                I confirm the scheduled date &amp; time:
                                <span class="block font-semibold text-gray-900" x-text="confirmedDateTimeLong()"></span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer rounded-lg bg-white/70 border border-emerald-200 p-3">
                            <input type="checkbox" x-model="confirmAmount" class="mt-1 w-5 h-5 accent-emerald-600">
                            <span class="text-sm text-gray-700">
                                I confirm the quoted amount:
                                <span class="block font-semibold text-lg text-gray-900" x-text="inquiry?.quoted_price ? '$' + money(inquiry.quoted_price) : '—'"></span>
                                <span x-show="forLabel()" x-cloak class="block text-xs text-gray-500">For: <span class="font-medium text-gray-700" x-text="forLabel()"></span></span>
                            </span>
                        </label>

                        <p class="text-xs font-medium text-emerald-800 pt-1">*Please call <a href="tel:{{ config('business.phoneRaw') }}" class="underline font-semibold">{{ config('business.phone') }}</a> if the date, time or quote are inaccurate.</p>
                    </div>

                    {{-- Signature --}}
                    <div x-ref="signatureField" class="rounded-xl transition-shadow" :class="invalidField === 'signatureField' && 'ring-2 ring-red-400 p-3 -m-3'">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block font-medium text-gray-700">Your Signature <span class="text-red-500">*</span></label>
                            <button type="button" @click="openSignaturePad()" class="text-xs font-semibold text-[#CA8A04] hover:text-[#A66B00] inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-[#EAB308]/40 hover:bg-[#F8C820]/10">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3m13-5v3a2 2 0 0 1-2 2h-3"/></svg> Sign in full screen
                            </button>
                        </div>
                        <div class="border-2 border-dashed border-gray-300 rounded-2xl bg-white p-2">
                            <template x-if="signatureDataUrl">
                                <img :src="signatureDataUrl" alt="Your signature" class="w-full h-40 object-contain rounded-xl bg-white border border-gray-200">
                            </template>
                            <template x-if="!signatureDataUrl">
                                <canvas x-ref="canvas" width="520" height="160"
                                        class="w-full touch-none rounded-xl bg-white border border-gray-200 cursor-crosshair"
                                        @mousedown="startDrawing($event)" @mousemove="draw($event)" @mouseup="endDrawing()" @mouseleave="endDrawing()"
                                        @touchstart.prevent="startDrawing($event)" @touchmove.prevent="draw($event)" @touchend="endDrawing()"></canvas>
                            </template>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <button type="button" @click="clearSignature()" class="text-xs text-gray-500 hover:text-gray-700">Clear signature</button>
                            <span class="text-[10px] text-gray-400">Tip: tap &ldquo;Sign in full screen&rdquo; for a bigger pad</span>
                        </div>
                    </div>

                    {{-- Equipment rental with no signed rental agreement on file --}}
                    <div x-show="needsAgreement" x-cloak class="rounded-xl border-2 border-amber-400 bg-amber-50 p-3 flex items-start gap-2">
                        <x-icon name="alert" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"/>
                        <p class="text-sm text-amber-800"><span class="font-bold">No rental agreement on file.</span> After you submit, we'll take you to the rental agreement to review and sign.</p>
                    </div>

                    <p x-show="error" x-text="error" class="text-red-600 text-sm" x-cloak></p>

                    <button type="submit" :disabled="submitting"
                            class="w-full btn-primary py-3.5 text-base disabled:opacity-60">
                        <span x-text="submitting ? 'Submitting…' : (needsAgreement ? 'Submit & Complete Rental Agreement' : 'Confirm & Submit Details')"></span>
                    </button>

                    <p class="text-center text-[10px] text-gray-400">This link can only be used once. After submitting you will receive confirmation.</p>
                </form>
            </div>

            <p class="text-center text-xs text-gray-500 mt-8">{{ config('business.name') }} &bull; Serving the Inland Empire since 2019</p>
        </div>
    </div>

    {{-- Full-screen signature pad --}}
    <div x-show="showSignaturePad" x-cloak class="fixed inset-0 z-[100] bg-charcoal-900/95 flex flex-col p-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-white font-semibold">Sign below</span>
            <button type="button" @click="showSignaturePad = false" class="text-white/80 hover:text-white p-2"><x-icon name="x" class="w-6 h-6"/></button>
        </div>
        <div class="flex-1 bg-white rounded-2xl overflow-hidden">
            <canvas x-ref="bigCanvas" class="w-full h-full touch-none cursor-crosshair"
                    @mousedown="startDrawing($event)" @mousemove="draw($event)" @mouseup="endDrawing()" @mouseleave="endDrawing()"
                    @touchstart.prevent="startDrawing($event)" @touchmove.prevent="draw($event)" @touchend="endDrawing()"></canvas>
        </div>
        <p class="text-center text-white/60 text-xs mt-2">Rotate your phone for more room. Sign with your finger or stylus.</p>
        <div class="flex gap-3 mt-2">
            <button type="button" @click="clearBigPad()" class="flex-1 py-3 rounded-xl border border-white/30 text-white font-medium active:bg-white/10">Clear</button>
            <button type="button" @click="useBigSignature()" class="flex-1 py-3 rounded-xl btn-primary">Use Signature</button>
        </div>
    </div>
</div>
@endsection
