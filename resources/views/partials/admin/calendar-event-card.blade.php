{{-- One day-timeline event card. Expects an Alpine `ev` in scope (from x-for) and
     $evStyleExpr (the :style expression) + $linkTarget. Used by both the single
     timeline and the per-employee columns. --}}
@php($linkTarget = $linkTarget ?? '_self')
<a :href="detailUrl(ev.inquiry.id)" target="{{ $linkTarget }}" @click.stop :style="{!! $evStyleExpr !!}" class="rounded-lg border transition-all overflow-hidden px-2 py-1 flex flex-col" :class="[eventClasses(ev.inquiry.status), ev.inquiry.type === 'pickup' ? 'border-dashed' : '']">
    {{-- Text content: clips top-down; reserves bottom space for the always-visible assignee --}}
    <div class="flex-1 min-h-0 overflow-hidden flex flex-col" :class="ev.inquiry.assigned_employee && 'pb-3.5'">
        <div class="shrink-0 flex items-start justify-between gap-1 mt-0.5">
            <div class="flex items-center gap-1 min-w-0">
                <div class="text-[10px] leading-none truncate min-w-0"><span class="font-mono text-amber-600" x-text="ev.inquiry.ref"></span><span class="text-gray-500" x-text="' - (' + fmtClock(ev.start) + ' - ' + fmtClock(ev.end) + ')'"></span></div>
                <span x-show="ev.inquiry.type === 'pickup'" x-cloak class="text-[9px] font-bold uppercase tracking-wide text-cyan-700 shrink-0">Pickup</span>
                <span x-show="ev.inquiry.before_visit" x-cloak title="Pickup is scheduled before the delivery visit" class="shrink-0"><x-icon name="alert" class="w-3 h-3 text-red-600"/></span>
            </div>
            {{-- Status bubble (top-right) --}}
            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-px rounded-full bg-white/95 border border-black/10 text-[8px] font-semibold text-gray-700 leading-none whitespace-nowrap"><span class="w-1.5 h-1.5 rounded-full" :class="dotClass(ev.inquiry.status)"></span><span x-text="statusLabel(ev.inquiry.status)"></span></span>
        </div>
        <div class="shrink-0 leading-tight truncate"><span class="font-semibold text-sm text-gray-900" x-text="ev.inquiry.name"></span><span class="text-gray-500 text-[10px] capitalize" x-text="' · ' + jobKind(ev.inquiry) + ' · ' + jobLabel(ev.inquiry)"></span></div>
        <div x-show="ev.big && ev.inquiry.address" x-cloak class="shrink-0 flex items-start gap-0.5 text-gray-400 text-[10px] leading-tight truncate"><x-icon name="map-pin" class="w-2.5 h-2.5 shrink-0 mt-px"/><span class="truncate" x-text="ev.inquiry.address"></span></div>
    </div>
    {{-- Assigned-to — always pinned to the bottom-right corner --}}
    <div x-show="ev.inquiry.assigned_employee" x-cloak class="absolute right-2 bottom-1 max-w-[calc(100%-1rem)] flex items-center gap-0.5 text-amber-700 text-[10px] leading-none"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
</a>
