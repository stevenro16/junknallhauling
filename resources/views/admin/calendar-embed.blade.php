@extends('layouts.bare')

@section('title', 'Day Calendar — '.config('business.name'))

@section('content')
<div class="min-h-screen bg-gray-100 p-3 pb-20"
     x-data="calendar({
        events: @js($events),
        detailBase: '{{ route('admin.inquiries.show', '__ID__') }}',
        initialView: 'day',
        initialDate: @js($date),
        pickTime: @js($time),
        pickDuration: @js($duration),
        pickLabel: @js($label),
        pickInquiryId: @js($exclude),
        pickTarget: @js($target),
        assignee: @js($assignee),
        assigneeName: @js($assigneeName),
        employees: @js($employees),
        embed: true,
     })">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <h2 class="text-base font-bold text-gray-900" x-text="headerLabel"></h2>
        <div class="flex items-center gap-2">
            {{-- Day / 3-Day toggle (defaults to Day) --}}
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
                <template x-for="m in [{v:'day',l:'Day'},{v:'3day',l:'3 Day'}]" :key="m.v">
                    <button @click="viewMode = m.v" class="px-3 py-1.5 transition-colors whitespace-nowrap" :class="viewMode === m.v ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="m.l"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
            <button @click="prev()" class="btn-outline p-1.5" :aria-label="viewMode === '3day' ? 'Previous 3 days' : 'Previous day'"><x-icon name="chevron-left" class="w-4 h-4"/></button>
            <button @click="next()" class="btn-outline p-1.5" :aria-label="viewMode === '3day' ? 'Next 3 days' : 'Next day'"><x-icon name="chevron-right" class="w-4 h-4"/></button>
        </div>
    </div>

    {{-- Assignee quick-filter (pre-selected to this quote's assignee; 2+ → columns) --}}
    @include('partials.admin.calendar-assignee-filter')

    {{-- Hint / placed status (day-pick view only) --}}
    <div x-show="viewMode === 'day' && pickedMinutes === null" class="mb-3 text-xs text-gray-500">Click a time to place this visit. Drag it to reschedule, or drag its bottom edge to change the duration.</div>
    <div x-show="viewMode === 'day' && pickedMinutes !== null" x-cloak class="mb-3 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        <span class="font-semibold" x-text="pickedLabelFull"></span> — drag to move, drag the bottom edge to resize.
    </div>
    <div x-show="viewMode === '3day'" x-cloak class="mb-3 text-xs text-gray-500">Tap a day to open its timeline and place this visit.</div>

    {{-- Day view (with click/drag to schedule) --}}
    @include('partials.admin.calendar-day', ['linkTarget' => '_top', 'pickMode' => true])

    {{-- 3-day view (read-only context; tap a day to open its timeline). Columns on desktop, stacked on mobile. --}}
    <div x-show="viewMode === '3day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid gap-px bg-gray-200 grid-cols-1 sm:grid-cols-3">
            <template x-for="(day, index) in rangeDays" :key="index">
                <div @click="goToDay(day)" class="bg-white p-2 cursor-pointer hover:bg-gray-50 transition-colors sm:min-h-[200px]" :class="isToday(day) && 'bg-amber-50'">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="text-xs font-semibold" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })"></div>
                        <span x-show="isToday(day)" class="text-[9px] uppercase font-bold tracking-wide text-amber-600 shrink-0">Today</span>
                    </div>
                    <div class="space-y-1.5">
                        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-[11px] text-gray-400 italic">No visits</div>
                        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.event_id">
                            <div class="block p-1.5 text-[11px] rounded-lg border" :class="[eventClasses(ev.inquiry.status), ev.inquiry.type === 'pickup' ? 'border-dashed' : '']">
                                <div class="flex items-start justify-between gap-1">
                                    <div class="flex items-center gap-1 min-w-0"><div class="text-[10px] leading-none truncate min-w-0"><span class="font-mono text-amber-600" x-text="ev.inquiry.ref"></span><span class="text-gray-500" x-text="' - (' + fmtClock(ev.start) + ' - ' + fmtClock(ev.end) + ')'"></span></div><span x-show="ev.inquiry.type === 'pickup'" x-cloak class="text-[9px] font-bold uppercase text-cyan-700 shrink-0">Pickup</span><span x-show="ev.inquiry.before_visit" x-cloak title="Pickup is scheduled before the delivery visit" class="shrink-0"><x-icon name="alert" class="w-3 h-3 text-red-600"/></span></div>
                                    <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-px rounded-full bg-white/95 border border-black/10 text-[9px] font-semibold text-gray-700 leading-none whitespace-nowrap"><span class="w-1.5 h-1.5 rounded-full" :class="dotClass(ev.inquiry.status)"></span><span x-text="statusLabel(ev.inquiry.status)"></span></span>
                                </div>
                                <div class="truncate"><span class="text-gray-800 font-medium" x-text="ev.inquiry.name"></span><span class="text-[10px] text-gray-500 capitalize" x-text="' · ' + jobKind(ev.inquiry) + ' · ' + jobLabel(ev.inquiry)"></span></div>
                                <div x-show="ev.inquiry.address" x-cloak class="text-[10px] text-gray-400 truncate" x-text="ev.inquiry.address"></div>
                                <div x-show="ev.inquiry.assigned_employee" x-cloak class="flex items-center justify-end gap-0.5 text-[10px] text-amber-700 mt-0.5"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Save Schedule — bottom-right corner of the calendar --}}
    <button type="button" x-show="pickedMinutes !== null" x-cloak @click="applyToQuote()"
            class="fixed bottom-4 right-4 z-50 btn-primary text-sm py-3 px-6 rounded-full shadow-lg">
        Save Schedule
    </button>
</div>
@endsection
