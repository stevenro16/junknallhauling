@extends('layouts.public')

@section('title', 'Dumpster Rental Contract Agreement | '.config('business.name'))

@section('content')
<div x-data="agreementForm('{{ $token }}')">
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

    {{-- Signed --}}
    <div x-show="signed" x-cloak class="min-h-screen bg-[#F8F7F4] flex items-center justify-center p-6">
        <div class="max-w-lg w-full bg-white rounded-2xl shadow p-8 text-center">
            <x-icon name="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"/>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 mb-2">Thank You!</h1>
            <p class="text-gray-600 mb-6">Your Rental Contract Agreement has been signed and securely attached to quote
                <span class="font-mono text-[#EAB308]" x-text="inquiry?.ref"></span>.</p>
            <p class="text-sm text-gray-500">You will receive a confirmation shortly. If you have any questions, call or text us at the number on your original quote.</p>
        </div>
    </div>

    {{-- Form --}}
    <div x-show="!loading && data && !signed" x-cloak class="min-h-screen bg-[#F8F7F4] py-10 px-4">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-6">
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-gray-900">Dumpster Rental Contract Agreement</h1>
                <p class="text-gray-700 mt-2 text-sm md:text-base">The customer agrees to the following terms and conditions for the dumpster for services.</p>
                <p class="text-xs text-gray-500 mt-1">Quote <span class="font-mono font-medium text-[#EAB308]" x-text="inquiry?.ref"></span></p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 md:p-8">
                <form @submit.prevent="submit()" class="space-y-8">
                    {{-- Customer Information --}}
                    <div>
                        <h2 class="font-semibold text-lg mb-4 text-gray-800">Customer Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" class="input-dark w-full" :value="firstName()" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" class="input-dark w-full" :value="lastName()" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                <input type="tel" class="input-dark w-full" :value="inquiry?.phone" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" class="input-dark w-full" :value="inquiry?.email" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                                <input type="text" class="input-dark w-full" :value="inquiry?.address || ''" required>
                            </div>
                        </div>
                    </div>

                    {{-- Rental Details --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <h2 class="font-semibold text-lg mb-4 text-gray-800">Rental Details (from your quote)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wider mb-0.5">Equipment Needed</div>
                                <div class="font-medium text-gray-900" x-text="inquiry?.equipment_type || '—'"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wider mb-0.5">Expected Duration</div>
                                <div class="font-medium text-gray-900" x-text="durationLabel()"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wider mb-0.5">Quoted Price</div>
                                <div class="font-semibold text-lg text-gray-900" x-text="inquiry?.quoted_price ? '$' + money(inquiry.quoted_price) : '—'"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wider mb-0.5">Pickup Address</div>
                                <div class="font-medium text-gray-900" x-text="inquiry?.address || '—'"></div>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 text-xs uppercase tracking-wider mb-0.5">Confirmed Date &amp; Time</div>
                            <div class="font-medium text-gray-900" x-text="confirmedDateTimeLong()"></div>
                        </div>
                        <div x-show="inquiry?.admin_notes" class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-gray-500 text-xs uppercase tracking-wider mb-1">Service Notes</div>
                            <div class="text-sm text-gray-700 whitespace-pre-wrap bg-white p-3 rounded border border-gray-200" x-text="inquiry?.admin_notes"></div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-3">These details were set when your quote was prepared and are locked for this agreement.</p>
                    </div>

                    {{-- Acknowledgments --}}
                    <div>
                        <h2 class="font-semibold text-lg mb-3 text-gray-800">Customer Acknowledgments</h2>
                        <div class="space-y-3 text-sm">
                            @foreach(config('agreement.acknowledgments') as $ack)
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" class="mt-1 w-4 h-4 accent-[#EAB308]" required>
                                    <span>{{ $ack }}</span>
                                </label>
                            @endforeach

                            <div class="pt-2">
                                <p class="font-medium mb-1">Prohibited Items:</p>
                                <p class="text-gray-700 text-sm">I understand that the following items are <strong>not allowed</strong> to be placed in the dumpster:
                                    {{ config('agreement.prohibited_items') }}</p>
                            </div>
                            <div>
                                <p class="text-sm">{{ config('agreement.tire_pricing') }}</p>
                                <p class="text-sm mt-1">{{ config('agreement.tire_note') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pickup Time --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Dumpster will be picked up on the day agreed upon and at the same time it was delivered. Please confirm time below.</label>
                        <div class="flex items-center gap-3">
                            <input type="time" x-model="pickupTime" class="input-dark w-40" required>
                            <span x-show="pickupTime" class="text-sm text-gray-600 font-medium" x-text="formatTime12Hour(pickupTime)"></span>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-gray-500 mr-1">Quick times:</span>
                            @foreach(['08:00' => '8:00 AM', '09:00' => '9:00 AM', '10:00' => '10:00 AM'] as $val => $lbl)
                                <button type="button" @click="pickupTime = '{{ $val }}'"
                                        class="px-3 py-1 text-xs rounded-lg border border-gray-300 hover:bg-[#F8C820] hover:text-charcoal-900 hover:border-[#F8C820] transition-all active:scale-95">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">Or use the time picker above. Time will be stored as <span x-text="pickupTime ? formatTime12Hour(pickupTime) : 'e.g. 8:30 AM'"></span></p>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">If the time entered on the contract differs from a prior conversation, we must confirm we can guarantee.</p>

                    {{-- Signature --}}
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Your Signature <span class="text-red-500">*</span></label>
                        <div class="border-2 border-dashed border-gray-300 rounded-2xl bg-white p-2">
                            <canvas x-ref="canvas" width="520" height="160"
                                    class="w-full touch-none rounded-xl bg-white border border-gray-200 cursor-crosshair"
                                    @mousedown="startDrawing($event)" @mousemove="draw($event)" @mouseup="endDrawing()" @mouseleave="endDrawing()"
                                    @touchstart.prevent="startDrawing($event)" @touchmove.prevent="draw($event)" @touchend="endDrawing()"></canvas>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <button type="button" @click="clearSignature()" class="text-xs text-gray-500 hover:text-gray-700">Clear signature</button>
                            <span class="text-[10px] text-gray-400">Sign with mouse, finger, or stylus</span>
                        </div>
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" x-model="pickupDate" class="input-dark w-full" required>
                    </div>

                    {{-- Final agreement --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" x-model="agreed" class="mt-1 w-4 h-4 accent-[#EAB308]" required>
                        <span class="text-sm text-gray-700">I have read, understand, and agree to all terms and conditions listed above for quote <span class="font-mono" x-text="inquiry?.ref"></span>.</span>
                    </label>

                    <p x-show="error" x-text="error" class="text-red-600 text-sm" x-cloak></p>

                    <button type="submit" :disabled="submitting || !agreed || !hasSignature || !pickupDate || !pickupTime"
                            class="w-full btn-primary py-3.5 text-base disabled:opacity-60">
                        <span x-text="submitting ? 'Submitting Agreement...' : 'Sign & Submit Dumpster Rental Contract Agreement'"></span>
                    </button>

                    <p class="text-center text-[10px] text-gray-400">This link can only be used once. After signing you will receive confirmation.</p>
                </form>
            </div>

            <p class="text-center text-xs text-gray-500 mt-8">{{ config('business.name') }} &bull; Serving the Inland Empire since 2019</p>
        </div>
    </div>
</div>
@endsection
