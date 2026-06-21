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
     })">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <h2 class="text-base font-bold text-gray-900" x-text="headerLabel"></h2>
        <div class="flex items-center gap-2">
            {{-- Day / Week toggle (defaults to Day) --}}
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
                <template x-for="mode in ['day','week']" :key="mode">
                    <button @click="viewMode = mode" class="px-3 py-1.5 transition-colors capitalize" :class="viewMode === mode ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="mode"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
            <button @click="prev()" class="btn-outline p-1.5" :aria-label="viewMode === 'week' ? 'Previous week' : 'Previous day'"><x-icon name="chevron-left" class="w-4 h-4"/></button>
            <button @click="next()" class="btn-outline p-1.5" :aria-label="viewMode === 'week' ? 'Next week' : 'Next day'"><x-icon name="chevron-right" class="w-4 h-4"/></button>
        </div>
    </div>

    {{-- Hint / placed status (day-pick view only) --}}
    <div x-show="viewMode === 'day' && pickedMinutes === null" class="mb-3 text-xs text-gray-500">Click a time to place this visit. Drag it to reschedule, or drag its bottom edge to change the duration.</div>
    <div x-show="viewMode === 'day' && pickedMinutes !== null" x-cloak class="mb-3 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        <span class="font-semibold" x-text="pickedLabelFull"></span> — drag to move, drag the bottom edge to resize.
    </div>
    <div x-show="viewMode === 'week'" x-cloak class="mb-3 text-xs text-gray-500">Click a day to switch to its timeline and place this visit.</div>

    {{-- Day view (with click/drag to schedule) --}}
    @include('partials.admin.calendar-day', ['linkTarget' => '_top', 'pickMode' => true])

    {{-- Week view (read-only context; click a day to schedule there) --}}
    <div x-show="viewMode === 'week'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div class="p-2 text-center text-[11px] font-medium text-gray-500 border-r border-gray-200 last:border-r-0">{{ $d }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            <template x-for="(day, index) in weekDays" :key="index">
                <div @click="goToDay(day)" class="min-h-[160px] p-2 border-r border-gray-200 last:border-r-0 cursor-pointer hover:bg-gray-50 transition-colors" :class="isToday(day) && 'bg-amber-50'">
                    <div class="text-xs font-medium mb-1.5" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric' })"></div>
                    <div class="space-y-1.5">
                        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-[11px] text-gray-400 italic">No visits</div>
                        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.id">
                            <div class="block p-1.5 text-[11px] rounded-lg border" :class="eventClasses(ev.inquiry.status)">
                                <div class="flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div><div class="font-mono text-amber-600 text-[10px]" x-text="ev.inquiry.ref"></div></div>
                                <div class="text-gray-800 truncate font-medium" x-text="ev.inquiry.name"></div>
                                <div class="text-gray-500 text-[10px] mt-0.5"><span x-text="fmtClock(ev.start)"></span></div>
                                <div x-show="ev.inquiry.assigned_employee" x-cloak class="flex items-center gap-0.5 text-[10px] text-amber-700 truncate"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
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
