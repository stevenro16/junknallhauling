{{-- Reusable "what's booked on this day" agenda + open-day-calendar button.
     Parameters (Alpine expression names, interpolated at render):
       $dateExpr   — datetime state (e.g. 'confirmedDateTime' / 'pickupDateTime')
       $schedule   — agenda getter   (e.g. 'daySchedule' / 'pickupDaySchedule')
       $conflict   — conflict count  (e.g. 'dayConflictCount' / 'pickupDayConflictCount')
       $other      — other count     (e.g. 'dayOtherCount' / 'pickupDayOtherCount')
       $modal      — modal flag       (e.g. 'showCalendarModal' / 'showPickupCalendarModal')
       $selfLabel  — label for this quote's own row ('This visit' / 'This pickup')
       $iconColor  — calendar icon color class (optional)
--}}
<div x-show="datePart({{ $dateExpr }})" x-cloak class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
    <div class="flex items-center justify-between gap-2 mb-2">
        <div class="text-xs font-semibold text-gray-700 inline-flex items-center gap-1.5">
            <x-icon name="calendar" class="w-3.5 h-3.5 {{ $iconColor ?? 'text-amber-500' }}"/>
            <span>Booked on <span x-text="new Date(datePart({{ $dateExpr }}) + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })"></span></span>
        </div>
        <button type="button" @click="{{ $modal }} = true" class="text-[10px] text-amber-600 hover:text-amber-700 inline-flex items-center gap-0.5 shrink-0">Open day calendar <x-icon name="external-link" class="w-2.5 h-2.5"/></button>
    </div>

    <div x-show="{{ $conflict }} > 0" x-cloak class="mb-2 text-[11px] text-red-700 bg-red-50 border border-red-200 rounded-lg px-2 py-1.5">
        &#9888;&#65039; <span x-text="{{ $conflict }} === 1 ? '1 visit overlaps this time slot' : {{ $conflict }} + ' visits overlap this time slot'"></span>
    </div>

    <div class="space-y-1">
        <template x-for="ev in {{ $schedule }}" :key="ev.id + '-' + ev.start.getTime()">
            <div class="flex items-start gap-2 px-2 py-1.5 rounded-lg border text-xs transition-colors"
                 :class="ev.isSelf ? 'border-[#F8C820]/60 bg-[#F8C820]/10' : (ev.conflict ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white')">
                <span class="font-mono text-gray-600 shrink-0 whitespace-nowrap pt-0.5" x-text="clock(ev.start) + '–' + clock(ev.end)"></span>
                <span class="w-1.5 h-1.5 rounded-full shrink-0 mt-1.5" :class="dotClass(ev.status)"></span>
                <div class="flex-1 min-w-0">
                    <div class="truncate">
                        <span class="font-medium text-gray-800" x-text="ev.isSelf ? '{{ $selfLabel ?? 'This visit' }}' : (ev.name || '(no name)')"></span>
                        <span x-show="!ev.isSelf" class="text-gray-400 capitalize" x-text="' · ' + serviceLabel(ev.service_type)"></span>
                    </div>
                    <div x-show="ev.address" x-cloak class="mt-0.5 flex items-start gap-1 text-gray-500">
                        <x-icon name="map-pin" class="w-3 h-3 shrink-0 mt-px text-gray-400"/>
                        <span class="truncate" x-text="ev.address"></span>
                    </div>
                    <div x-show="ev.assigned_employee" x-cloak class="mt-0.5 flex items-center gap-1 text-amber-700">
                        <x-icon name="user" class="w-3 h-3 shrink-0 text-amber-500"/>
                        <span class="truncate" x-text="ev.assigned_employee"></span>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0 pt-0.5">
                    <span x-show="ev.conflict" x-cloak class="text-[10px] font-semibold text-red-600">conflict</span>
                    <a x-show="!ev.isSelf" :href="detailUrl(ev.id)" class="text-amber-600 hover:text-amber-700" title="Open quote"><x-icon name="external-link" class="w-3 h-3"/></a>
                </div>
            </div>
        </template>
        <div x-show="{{ $other }} === 0" x-cloak class="text-[11px] text-emerald-600 px-1 py-0.5">&check; No other visits booked this day.</div>
    </div>
</div>
