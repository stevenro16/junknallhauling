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
        field: {{ ($field ?? false) ? 'true' : 'false' }},
        recordUrl: '{{ ($field ?? false) ? route('admin.field.payment', $inquiry->id) : '' }}',
        paidMethod: @js($inquiry->payment_method),
        paidAt: @js($inquiry->payment_date),
        quoted: @js($inquiry->quoted_price),
    })"
    @if($syncContact ?? true) x-effect="preferred = preferredContactMethod" @endif
    class="bg-white border border-gray-200 rounded-xl shadow-sm border-l-4 border-l-emerald-500 p-5">

    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="text-base font-semibold text-gray-800">{{ ($field ?? false) ? 'Payment' : 'Payment Link' }}</div>
        <button type="button" @click="send()" :disabled="sending" class="btn-primary text-xs py-1.5 px-3 shrink-0">
            <span x-text="sending ? 'Generating…' : '{{ ($field ?? false) ? 'Pay link / QR' : 'Generate Payment Link' }}'"></span>
        </button>
    </div>

    @if($field ?? false)
        {{-- In-field collection: record how the customer paid right now --}}
        <div class="rounded-lg border border-gray-200 p-3 mb-3">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="text-sm font-semibold text-gray-700">Amount due</div>
                <div x-show="hasAmount" x-cloak class="text-sm font-bold text-emerald-600">$<span x-text="money(quoted)"></span></div>
            </div>

            {{-- No price was quoted — let them enter it right here --}}
            <div x-show="!hasAmount" x-cloak class="mb-2">
                <label class="block text-xs text-gray-500 mb-1">Enter the amount due</label>
                <div class="relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                    <input type="number" inputmode="decimal" min="0" step="0.01" x-model="amountInput" @input="error = ''" placeholder="0.00" class="input-light text-sm py-1.5 pl-6 w-full">
                </div>
            </div>

            {{-- Already recorded as paid --}}
            <template x-if="paidMethod">
                <div class="flex items-center justify-between gap-2 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2">
                    <div class="text-sm text-emerald-800">&check; Paid via <span class="font-semibold" x-text="paidMethod"></span><span x-show="paidAt" class="text-emerald-600/80"> · <span x-text="fmt(paidAt)"></span></span></div>
                    <button type="button" @click="paidMethod = ''" class="text-xs text-emerald-700 hover:text-emerald-900 underline shrink-0">Change</button>
                </div>
            </template>

            {{-- Record an in-person payment --}}
            <template x-if="!paidMethod">
                <div>
                    <div class="text-xs text-gray-500 mb-2">Mark how the customer paid in person:</div>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <template x-for="m in ['Cash','Check','Credit/Debit Card','Venmo','Zelle']" :key="m">
                            <button type="button" @click="method = (method === m ? '' : m)"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors"
                                    :class="method === m ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white border-gray-300 text-gray-700 hover:border-emerald-400 hover:bg-emerald-50'"
                                    x-text="m"></button>
                        </template>
                    </div>
                    <button type="button" @click="recordPaid()" :disabled="recording || !method" class="btn-primary text-sm py-2 px-4 w-full sm:w-auto disabled:opacity-50">
                        <span x-text="recording ? 'Saving…' : 'Mark Paid'"></span>
                    </button>
                </div>
            </template>
        </div>
        <p class="text-xs text-gray-500 mb-3">Or have the customer pay online — generate a link to the saved Quoted Price and show the QR for them to scan. Tick the box to also text/email it.</p>
    @else
        <p class="text-xs text-gray-500 mb-3">Generates a link to the quoted price for the customer to pay online. Uses the <span class="font-medium">saved</span> Quoted Price — save the quote first if you just changed it. Check the box below to also send it.</p>
    @endif

    {{-- Optionally deliver the link to the customer's preferred contact method --}}
    <label class="flex items-center gap-2 mb-3 text-sm text-gray-700 cursor-pointer select-none">
        <input type="checkbox" x-model="sendToContact" class="w-4 h-4 accent-emerald-500">
        <span x-text="contactLabel"></span>
    </label>

    {{-- Active payment link --}}
    <div x-show="link" x-cloak class="space-y-2">
        {{-- Scan-to-pay QR (Field View) --}}
        <div x-show="field && qr" x-cloak class="flex flex-col items-center gap-1 pb-1">
            <img :src="qr" alt="Scan to pay" class="w-40 h-40 rounded-lg border border-gray-200 bg-white p-1">
            <div class="text-[11px] text-gray-500">Have the customer scan to pay</div>
        </div>
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
