{{-- Payment-link sender — generate a payment link for the quoted price and send
     it to the customer. Self-contained; reads its own data. --}}
<div x-data="paymentSender({
        createUrl: '{{ route('admin.api.inquiries.payment-link', $inquiry->id) }}',
        deleteUrl: '{{ route('admin.api.payment-link.destroy', '__ID__') }}',
        links: @js($paymentLinks),
        preferred: @js($inquiry->preferred_contact_method),
        phone: @js($inquiry->phone),
        email: @js($inquiry->email),
        name: @js($inquiry->name),
    })"
    @if($syncContact ?? true) x-effect="preferred = preferredContactMethod" @endif
    class="bg-white border border-gray-200 rounded-xl shadow-sm border-l-4 border-l-emerald-500 p-5">

    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="text-base font-semibold text-gray-800">Payment Link</div>
        <button type="button" @click="send()" :disabled="sending" class="btn-primary text-xs py-1.5 px-3 shrink-0">
            <span x-text="sending ? 'Generating…' : 'Generate Payment Link'"></span>
        </button>
    </div>
    <p class="text-xs text-gray-500 mb-3">Generates a link to the quoted price for the customer to pay online. Uses the <span class="font-medium">saved</span> Quoted Price — save the quote first if you just changed it. Check the box below to also send it.</p>

    {{-- Optionally deliver the link to the customer's preferred contact method --}}
    <label class="flex items-center gap-2 mb-3 text-sm text-gray-700 cursor-pointer select-none">
        <input type="checkbox" x-model="sendToContact" class="w-4 h-4 accent-emerald-500">
        <span x-text="contactLabel"></span>
    </label>

    {{-- Active payment link --}}
    <div x-show="link" x-cloak class="space-y-2">
        <label class="block text-[10px] uppercase tracking-widest text-gray-400">Payment link <span x-show="amount !== null" x-cloak class="text-emerald-600 normal-case tracking-normal">— $<span x-text="money(amount)"></span></span></label>
        <div class="flex items-center gap-2">
            <input type="text" readonly :value="link" @focus="$event.target.select()"
                   class="input-light text-xs py-1.5 flex-1">
            <button type="button" @click="copy()" class="btn-outline !px-3 !py-1.5 text-xs whitespace-nowrap"
                    x-text="copied ? 'Copied!' : 'Copy'"></button>
        </div>
        <a :href="link" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700">
            Open payment page <x-icon name="external-link" class="w-3 h-3"/>
        </a>
    </div>

    <p x-show="error" x-text="error" x-cloak class="text-red-500 text-xs mt-2"></p>

    {{-- Existing payment links --}}
    <template x-if="links.length">
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="text-[10px] uppercase tracking-widest text-gray-400 mb-2">History</div>
            <div class="space-y-1.5">
                <template x-for="l in links" :key="l.id">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-1.5 py-0.5 rounded border font-medium shrink-0"
                              :class="l.paid_at
                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                  : (l.cancelled_at ? 'bg-gray-100 text-gray-500 border-gray-300' : 'bg-amber-50 text-amber-700 border-amber-200')"
                              x-text="l.paid_at ? 'Paid' : (l.cancelled_at ? 'Cancelled' : 'Awaiting payment')"></span>
                        <span class="font-medium text-gray-700 shrink-0">$<span x-text="money(l.amount)"></span></span>
                        <span class="text-gray-500 shrink-0" x-text="fmt(l.paid_at || l.created_at)"></span>
                        <div class="ml-auto flex items-center gap-2">
                            <button type="button" @click="copyLink(l)" class="text-emerald-600 hover:text-emerald-700 shrink-0"
                                    x-text="copiedId === l.id ? 'Copied!' : 'Copy'"></button>
                            <a :href="l.url" target="_blank" rel="noopener" class="text-emerald-600 hover:text-emerald-700 shrink-0">Open</a>
                            <button type="button" @click="remove(l)" class="text-red-500 hover:text-red-600 shrink-0" title="Delete">
                                <x-icon name="trash" class="w-3.5 h-3.5"/>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
