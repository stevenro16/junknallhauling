{{-- Condensed rental-agreement control — lives at the bottom of Job Details
     (equipment rentals only). One agreement at a time:
       • none yet      → "Text/Email Rental Agreement Link" (generates + sends)
       • generated     → "Rental Agreement Link" (copies the sent link) + status
       • signed        → View / Print the full signed agreement
     Reads its own data; syncs `preferred` with the live contact method. --}}
<div class="pt-3 border-t border-gray-200"
     x-data="agreementSender({
        createUrl: '{{ route('admin.api.inquiries.agreement', $inquiry->id) }}',
        deleteUrl: '{{ route('admin.api.rental-agreement.destroy', '__ID__') }}',
        agreements: @js($agreements),
        preferred: @js($inquiry->preferred_contact_method),
        phone: @js($inquiry->phone),
        email: @js($inquiry->email),
        name: @js($inquiry->name),
     })"
     x-effect="preferred = preferredContactMethod">

    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-medium text-gray-700">Rental Agreement</div>

        {{-- No agreement yet → generate + text/email --}}
        <button type="button" x-show="!current" @click="send()" :disabled="sending"
                class="btn-primary text-xs py-1.5 px-3 shrink-0 inline-flex items-center gap-1.5">
            <x-icon name="send" class="w-3.5 h-3.5"/>
            <span x-text="sending ? 'Sending…' : contactLabel"></span>
        </button>

        {{-- Generated, awaiting signature → button copies the sent link --}}
        <button type="button" x-show="current && !current.signed_at" x-cloak @click="copyCurrent()"
                class="btn-outline text-xs !py-1.5 !px-3 shrink-0 inline-flex items-center gap-1.5">
            <x-icon name="external-link" class="w-3.5 h-3.5"/>
            <span x-text="copied ? 'Copied!' : 'Rental Agreement Link'"></span>
        </button>
    </div>

    {{-- Awaiting-signature status --}}
    <div x-show="current && !current.signed_at" x-cloak class="mt-2 flex items-center justify-between gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Awaiting signature · sent <span x-text="fmt(current.created_at)"></span></span>
        <button type="button" @click="remove(current)" class="text-red-500 hover:text-red-600">Delete</button>
    </div>

    {{-- Signed → full signed agreement + print --}}
    <div x-show="current && current.signed_at" x-cloak class="mt-2 flex items-center justify-between gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 text-emerald-700"><x-icon name="check-circle" class="w-3.5 h-3.5"/> Signed <span x-text="fmt(current.signed_at)"></span></span>
        <div class="flex items-center gap-3 shrink-0">
            <a :href="current.admin_url" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700 font-medium inline-flex items-center gap-1">View / Print <x-icon name="external-link" class="w-3 h-3"/></a>
            <button type="button" @click="remove(current)" class="text-red-500 hover:text-red-600">Delete</button>
        </div>
    </div>

    <p x-show="error" x-text="error" x-cloak class="text-red-500 text-xs mt-2"></p>
</div>
