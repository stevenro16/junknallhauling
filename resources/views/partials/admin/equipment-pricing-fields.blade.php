{{-- Pricing-type toggle + only the relevant fields. $s = the Alpine state object
     name ('nw' for the add form, 'ed' for the edit modal). --}}
<div>
    <label class="block text-xs text-gray-400 mb-1.5">Pricing type</label>
    <div class="inline-flex rounded-lg border border-charcoal-600 overflow-hidden text-sm">
        <button type="button" @click="{{ $s }}.pricingType = 'machinery'"
                :class="{{ $s }}.pricingType === 'machinery' ? 'bg-brand-yellow text-charcoal-900' : 'bg-charcoal-800 text-gray-300 hover:bg-charcoal-700'"
                class="px-3 sm:px-4 py-1.5 font-medium transition-colors">Machinery <span class="hidden sm:inline">(hourly / daily)</span></button>
        <button type="button" @click="{{ $s }}.pricingType = 'flat'"
                :class="{{ $s }}.pricingType === 'flat' ? 'bg-brand-yellow text-charcoal-900' : 'bg-charcoal-800 text-gray-300 hover:bg-charcoal-700'"
                class="px-3 sm:px-4 py-1.5 font-medium border-l border-charcoal-600 transition-colors">Flat-rate <span class="hidden sm:inline">(dumpster / trailer)</span></button>
    </div>
</div>

{{-- Machinery: hourly + optional daily --}}
<div x-show="{{ $s }}.pricingType === 'machinery'" class="grid grid-cols-2 gap-3 max-w-md">
    <div><label class="block text-xs text-gray-400 mb-1">Avg $/hr</label><input type="number" x-model="{{ $s }}.cost" class="input-dark" placeholder="e.g. 85"></div>
    <div><label class="block text-xs text-gray-400 mb-1">Daily Rate <span class="text-gray-500">(optional)</span></label><input type="number" x-model="{{ $s }}.daily" class="input-dark" placeholder="(optional)"></div>
</div>

{{-- Flat-rate: base price including days + tons, with overage rates --}}
<div x-show="{{ $s }}.pricingType === 'flat'" x-cloak class="space-y-2">
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div><label class="block text-xs text-gray-400 mb-1">Base price</label><input type="number" x-model="{{ $s }}.flat" class="input-dark" placeholder="349"></div>
        <div><label class="block text-xs text-gray-400 mb-1">Incl. days</label><input type="number" x-model="{{ $s }}.incDays" class="input-dark" placeholder="7"></div>
        <div><label class="block text-xs text-gray-400 mb-1">Incl. tons</label><input type="number" x-model="{{ $s }}.incTons" class="input-dark" placeholder="1"></div>
        <div><label class="block text-xs text-gray-400 mb-1">$ / extra ton</label><input type="number" x-model="{{ $s }}.addTon" class="input-dark" placeholder="84"></div>
        <div><label class="block text-xs text-gray-400 mb-1">$ / extra day</label><input type="number" x-model="{{ $s }}.addDay" class="input-dark" placeholder="15"></div>
    </div>
    <p class="text-[11px] text-gray-500">Base price covers the included days + tons, delivery &amp; pickup. Overages are billed per extra ton / day.</p>
</div>
