{{-- Rental agreement sender — generate a signing link to send the customer.
     Self-contained; reads its own data (createUrl + existing agreements). --}}
<div x-data="agreementSender({
        createUrl: '{{ route('admin.api.inquiries.agreement', $inquiry->id) }}',
        deleteUrl: '{{ route('admin.api.rental-agreement.destroy', '__ID__') }}',
        agreements: @js($agreements),
        preferred: @js($inquiry->preferred_contact_method),
        phone: @js($inquiry->phone),
        email: @js($inquiry->email),
        name: @js($inquiry->name),
    })"
    x-effect="preferred = preferredContactMethod"
    class="bg-white border border-gray-200 rounded-xl shadow-sm border-l-4 border-l-brand-yellow p-5">

    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="text-base font-semibold text-gray-800">Rental Agreement</div>
        <button type="button" @click="send()" :disabled="sending" class="btn-primary text-xs py-1.5 px-3 shrink-0">
            <span x-text="sending ? 'Generating…' : 'Generate Rental Agreement'"></span>
        </button>
    </div>
    <p class="text-xs text-gray-500 mb-3">Generate a signing link to send the customer at any point in the workflow.</p>

    {{-- Optionally deliver the link to the customer's preferred contact method --}}
    <label class="flex items-center gap-2 mb-3 text-sm text-gray-700 cursor-pointer select-none">
        <input type="checkbox" x-model="sendToContact" class="w-4 h-4 accent-orange-500">
        <span x-text="contactLabel"></span>
    </label>

    {{-- Active signing link --}}
    <div x-show="link" x-cloak class="space-y-2">
        <label class="block text-[10px] uppercase tracking-widest text-gray-400">Signing link</label>
        <div class="flex items-center gap-2">
            <input type="text" readonly :value="link" @focus="$event.target.select()"
                   class="input-light text-xs py-1.5 flex-1">
            <button type="button" @click="copy()" class="btn-outline !px-3 !py-1.5 text-xs whitespace-nowrap"
                    x-text="copied ? 'Copied!' : 'Copy'"></button>
        </div>
        <a :href="link" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700">
            Open signing page <x-icon name="external-link" class="w-3 h-3"/>
        </a>
    </div>

    <p x-show="error" x-text="error" x-cloak class="text-red-500 text-xs mt-2"></p>

    {{-- Existing agreements --}}
    <template x-if="agreements.length">
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="text-[10px] uppercase tracking-widest text-gray-400 mb-2">History</div>
            <div class="space-y-1.5">
                <template x-for="a in agreements" :key="a.id">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-1.5 py-0.5 rounded border font-medium shrink-0"
                              :class="a.signed_at
                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                  : (a.cancelled_at ? 'bg-gray-100 text-gray-500 border-gray-300' : 'bg-amber-50 text-amber-700 border-amber-200')"
                              x-text="a.signed_at ? 'Signed' : (a.cancelled_at ? 'Cancelled' : 'Awaiting signature')"></span>
                        <span class="text-gray-500 shrink-0" x-text="fmt(a.signed_at || a.created_at)"></span>
                        <div class="ml-auto flex items-center gap-2">
                            <img x-show="a.signature_base64" :src="a.signature_base64" alt="Signature"
                                 class="h-6 max-w-[90px] object-contain rounded border border-gray-200 bg-white">
                            <button type="button" @click="copyAgreement(a)" class="text-amber-600 hover:text-amber-700 shrink-0"
                                    x-text="copiedId === a.id ? 'Copied!' : 'Copy'"></button>
                            <a :href="a.admin_url" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700 shrink-0">View</a>
                            <button type="button" @click="remove(a)" class="text-red-500 hover:text-red-600 shrink-0" title="Delete">
                                <x-icon name="trash" class="w-3.5 h-3.5"/>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
