{{-- Reusable "what's booked on this day" agenda + open-day-calendar button.
     Bookings are grouped into one column per assignee (the job's assignee first,
     Unassigned last) — the same column approach as the calendars.
     Parameters (Alpine expression names, interpolated at render):
       $dateExpr   — datetime state   (e.g. 'confirmedDateTime' / 'pickupDateTime')
       $columns    — per-assignee columns getter (e.g. 'dayScheduleColumns')
       $conflict   — conflict count   (e.g. 'dayConflictCount')
       $other      — other count      (e.g. 'dayOtherCount')
       $modal      — modal flag        (e.g. 'showCalendarModal')
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

    {{-- Per-assignee timeline (6am–5pm) so gaps between bookings are easy to see --}}
    <div x-show="{{ $columns }}.length > 0" x-cloak class="rounded-lg border border-gray-200 bg-white overflow-hidden">
        {{-- Column headers --}}
        <div class="flex border-b border-gray-200 bg-gray-50">
            <div class="w-9 shrink-0"></div>
            <template x-for="col in {{ $columns }}" :key="col.id">
                <div class="flex-1 min-w-0 px-1.5 py-1 text-center border-l border-gray-200">
                    <div class="text-[11px] font-semibold truncate inline-flex items-center gap-1 justify-center" :class="col.isUnassigned ? 'text-gray-400' : 'text-gray-700'">
                        <x-icon name="user" class="w-3 h-3 shrink-0"/><span class="truncate" x-text="col.name"></span>
                    </div>
                </div>
            </template>
        </div>
        {{-- Timeline body --}}
        <div class="flex" :style="`height:${(panelEndHour - panelStartHour) * panelHourPx}px`">
            {{-- Hour gutter --}}
            <div class="w-9 shrink-0 relative">
                <template x-for="h in panelHours" :key="h">
                    <div class="absolute right-1 text-[9px] text-gray-400 -translate-y-1.5" :style="`top:${(h - panelStartHour) * panelHourPx}px`" x-text="panelHourLabel(h)"></div>
                </template>
            </div>
            {{-- Columns --}}
            <template x-for="col in {{ $columns }}" :key="col.id">
                <div class="flex-1 min-w-0 relative border-l border-gray-100 cursor-pointer"
                     @click="placeVisitAt($event, col, '{{ $kind ?? 'visit' }}')"
                     title="Click to set this {{ $kind ?? 'visit' }}'s time">
                    <template x-for="h in panelHours" :key="h">
                        <div class="absolute left-0 right-0 border-t border-gray-100" :style="`top:${(h - panelStartHour) * panelHourPx}px`"></div>
                    </template>
                    <template x-for="ev in col.rows" :key="ev.id + '-' + ev.start.getTime()">
                        <a :href="(ev.isSelf || ev.isDelivery) ? null : detailUrl(ev.id)" :target="(ev.isSelf || ev.isDelivery) ? null : '_blank'" :rel="(ev.isSelf || ev.isDelivery) ? null : 'noopener'"
                           :title="(ev.isSelf ? 'Drag to reschedule' : ev.isDelivery ? 'Drop-off visit' : (ev.name || '') + ' (opens in a new tab)') + ' · ' + clock(ev.start) + '–' + clock(ev.end)"
                           @pointerdown="ev.isSelf && startPanelDrag(ev, '{{ $kind ?? 'visit' }}', $event)"
                           class="absolute rounded border px-1 py-0.5 overflow-hidden block leading-tight select-none"
                           :style="`top:${panelTop(ev)}px;height:${panelHeight(ev)}px;left:2px;right:2px` + (ev.isSelf ? ';cursor:grab;touch-action:none' : '')"
                           :class="ev.isSelf ? 'border-[#F8C820]/70 bg-[#F8C820]/20' : ev.isDelivery ? 'border-cyan-400 bg-cyan-100/80 text-cyan-900' : (ev.conflict ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white hover:bg-amber-50')">
                            <div class="font-mono text-[8px] text-gray-500 truncate" x-text="clock(ev.start)"></div>
                            <div class="text-[9px] font-medium text-gray-800 truncate" x-text="ev.isSelf ? '{{ $selfLabel ?? 'This visit' }}' : ev.isDelivery ? 'Drop-off visit' : (ev.name || '(no name)')"></div>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div x-show="{{ $columns }}.length === 0" x-cloak class="text-[11px] text-gray-400 px-1 py-1 italic">Nothing booked for this assignment on this day.</div>
    <div x-show="{{ $columns }}.length > 0 && {{ $other }} === 0" x-cloak class="text-[11px] text-emerald-600 px-1 py-1 mt-1">&check; No other visits booked this day.</div>
</div>
