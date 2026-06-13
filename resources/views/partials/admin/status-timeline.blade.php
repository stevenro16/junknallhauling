{{-- Reads inquiryDetail() Alpine scope. Ported from InquiryStatusTimeline.tsx --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm border-l-4 border-l-brand-yellow p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="text-base font-semibold text-gray-800">Status Timeline</div>
        <div class="text-[10px] uppercase tracking-widest text-gray-400">Click to advance</div>
    </div>

    <div class="relative pl-1">
        <div class="absolute left-3.75 top-3 bottom-3 w-px bg-gray-200"></div>
        <div class="space-y-1">
            <template x-for="(step, idx) in flow" :key="step">
                <button type="button" @click="(step !== status && !saving) && quickUpdateStatus(step)" :disabled="saving || step === status"
                        class="relative group w-full flex items-center gap-3 rounded-lg px-2 py-2 text-left transition-all active:scale-[0.985] disabled:cursor-default"
                        :class="step === status ? 'bg-[#fffbeb] border border-amber-200' : (flowIndex >= 0 && idx < flowIndex ? 'hover:bg-gray-100' : 'hover:bg-gray-50 opacity-80 hover:opacity-100')">
                    <div class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold transition-all"
                         :class="step === status ? 'bg-brand-yellow text-gray-900 border-amber-400' : (flowIndex >= 0 && idx < flowIndex ? 'bg-emerald-500 text-white border-emerald-400' : 'bg-white text-gray-400 border-gray-300 group-hover:border-amber-300')">
                        <template x-if="flowIndex >= 0 && idx < flowIndex"><x-icon name="check" class="h-3.5 w-3.5"/></template>
                        <span x-show="!(flowIndex >= 0 && idx < flowIndex)" x-text="idx + 1"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm" :class="step === status ? 'text-amber-700' : (flowIndex >= 0 && idx < flowIndex ? 'text-emerald-600' : 'text-gray-600')" x-text="statusLabel(step)"></div>
                        <div x-show="step === status" class="text-[10px] text-amber-600/70">Current status</div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    {{-- Off-path actions --}}
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="text-[10px] uppercase tracking-widest text-gray-400 mb-2 px-1">Other Actions</div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="quickUpdateStatus('left_voicemail')" :disabled="saving || status === 'left_voicemail'"
                    class="px-3 py-1 text-xs rounded-lg border transition-all active:scale-[0.985] disabled:opacity-50"
                    :class="status === 'left_voicemail' ? 'bg-yellow-50 text-yellow-700 border-yellow-300' : 'border-gray-300 text-gray-600 hover:border-yellow-400 hover:text-yellow-700 hover:bg-yellow-50'">Left Voicemail</button>
            <button type="button" @click="quickUpdateStatus('cancelled')" :disabled="saving || status === 'cancelled'"
                    class="px-3 py-1 text-xs rounded-lg border transition-all active:scale-[0.985] disabled:opacity-50"
                    :class="status === 'cancelled' ? 'bg-gray-100 text-gray-600 border-gray-300' : 'border-gray-300 text-gray-600 hover:border-red-400 hover:text-red-600 hover:bg-red-50'">Cancel</button>
        </div>
    </div>

    {{-- Verification checklist --}}
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between mb-2 px-1">
            <div class="text-sm font-semibold text-gray-700">Verification Checklist</div>
            <span x-show="allVerified" class="text-[10px] px-2 py-0.5 rounded bg-emerald-50 text-emerald-600 border border-emerald-200">Ready for next step</span>
        </div>
        <div class="space-y-1">
            <label class="flex items-center gap-2.5 text-sm px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" :checked="quoteVerified" @change="toggleVerification('quote_verified', $event.target.checked)" class="w-4 h-4 accent-orange-500" :disabled="saving">
                <span :class="quoteVerified ? 'text-emerald-600 line-through opacity-70' : 'text-gray-700'">Quote Verified</span>
            </label>
            <label class="flex items-center gap-2.5 text-sm px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" :checked="addressVerified" @change="toggleVerification('address_verified', $event.target.checked)" class="w-4 h-4 accent-orange-500" :disabled="saving">
                <span :class="addressVerified ? 'text-emerald-600 line-through opacity-70' : 'text-gray-700'">Address Verified</span>
            </label>
            <label class="flex items-center gap-2.5 text-sm px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" :checked="dateTimeVerified" @change="toggleVerification('date_time_verified', $event.target.checked)" class="w-4 h-4 accent-orange-500" :disabled="saving">
                <span :class="dateTimeVerified ? 'text-emerald-600 line-through opacity-70' : 'text-gray-700'">Date/Time Verified</span>
            </label>
        </div>
        <p class="text-[10px] text-gray-400 mt-2 px-2 leading-tight">All three must be checked before moving to <span class="font-medium text-amber-700">Scheduled</span> or <span class="font-medium text-amber-700">Service Performed</span>.</p>
    </div>
</div>
