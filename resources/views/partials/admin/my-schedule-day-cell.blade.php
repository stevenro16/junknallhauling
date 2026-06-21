{{-- One day column/row for the tech My Schedule 3-day / week grids. Uses `day`
     from the surrounding x-for scope. Tap the day to open its timeline. --}}
<div @click="goToDay(day)" class="bg-white p-2 cursor-pointer hover:bg-gray-50 transition-colors sm:min-h-[160px]" :class="isToday(day) && 'bg-amber-50'">
    <div class="flex items-center justify-between gap-1 mb-1.5">
        <div class="text-xs font-semibold truncate" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })"></div>
        <span x-show="isToday(day)" class="text-[9px] uppercase font-bold tracking-wide text-amber-600 shrink-0">Today</span>
    </div>
    <div class="space-y-1.5">
        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-[11px] text-gray-400 italic">—</div>
        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.event_id">
            <a :href="detailUrl(ev.inquiry.id)" @click.stop class="block p-1.5 text-[11px] rounded-lg border overflow-hidden transition-all" :class="[eventClasses(ev.inquiry.status), ev.inquiry.type === 'pickup' ? 'border-dashed' : '']">
                <div class="flex items-start justify-between gap-1">
                    <div class="flex items-center gap-1 min-w-0"><div class="text-[10px] leading-none truncate min-w-0"><span class="font-mono text-amber-600" x-text="ev.inquiry.ref"></span><span class="text-gray-500" x-text="' - (' + fmtClock(ev.start) + ' - ' + fmtClock(ev.end) + ')'"></span></div><span x-show="ev.inquiry.type === 'pickup'" x-cloak class="text-[9px] font-bold uppercase text-cyan-700 shrink-0">Pickup</span><span x-show="ev.inquiry.before_visit" x-cloak title="Pickup is scheduled before the delivery visit" class="shrink-0"><x-icon name="alert" class="w-3 h-3 text-red-600"/></span></div>
                    {{-- status bubble: shrinks + truncates so it never bleeds out of the card --}}
                    <span class="min-w-0 inline-flex items-center gap-1 px-1.5 py-px rounded-full bg-white/95 border border-black/10 text-[9px] font-semibold text-gray-700 leading-none"><span class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></span><span class="truncate min-w-0" x-text="statusLabel(ev.inquiry.status)"></span></span>
                </div>
                <div class="truncate"><span class="text-gray-800 font-medium" x-text="ev.inquiry.name"></span><span class="text-[10px] text-gray-500 capitalize" x-text="' · ' + jobKind(ev.inquiry) + ' · ' + jobLabel(ev.inquiry)"></span></div>
                <div x-show="ev.inquiry.address" x-cloak class="text-[10px] text-gray-400 truncate" x-text="ev.inquiry.address"></div>
                <div x-show="ev.inquiry.assigned_employee" x-cloak class="flex items-center justify-end gap-0.5 text-[10px] text-amber-700 mt-0.5"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
            </a>
        </template>
    </div>
</div>
