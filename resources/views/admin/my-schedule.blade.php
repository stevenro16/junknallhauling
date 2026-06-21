@extends('layouts.admin')

@section('title', 'My Schedule — '.config('business.name'))

@section('admin-content')
<div class="w-full" x-data="calendar({ events: @js($events), detailBase: '{{ route('admin.my-schedule.job', '__ID__') }}', initialView: 'day' })">
    {{-- Header --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold">My Schedule</h2>
            <p class="text-sm text-gray-500">Your assigned visits</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                <template x-for="mode in ['day','week']" :key="mode">
                    <button @click="viewMode = mode" class="px-4 py-1.5 transition-colors capitalize" :class="viewMode === mode ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="mode"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-sm px-4 py-2">Today</button>
            <button @click="prev()" class="btn-outline p-2" aria-label="Previous"><x-icon name="chevron-left" class="w-5 h-5"/></button>
            <button @click="next()" class="btn-outline p-2" aria-label="Next"><x-icon name="chevron-right" class="w-5 h-5"/></button>
        </div>
    </div>

    <div class="text-center mb-5">
        <h2 class="text-2xl font-black" x-text="headerLabel"></h2>
        <p class="text-sm text-gray-500 mt-1"><span x-text="totalOnCalendar"></span> assigned visit<span x-show="totalOnCalendar !== 1">s</span></p>
    </div>

    {{-- Day view --}}
    @include('partials.admin.calendar-day')

    {{-- Week view --}}
    <div x-show="viewMode === 'week'" class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div class="p-2 text-center text-xs font-medium text-gray-500 border-r border-gray-200 last:border-r-0">{{ $d }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            <template x-for="(day, index) in weekDays" :key="index">
                <div @click="eventsForKey(dayKey(day)).length > 0 && goToDay(day)" class="min-h-[200px] p-2 border-r border-gray-200 last:border-r-0 cursor-pointer hover:bg-gray-50 transition-colors" :class="isToday(day) && 'bg-amber-50'">
                    <div class="text-xs font-medium mb-2" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric' })"></div>
                    <div class="space-y-1.5">
                        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-[11px] text-gray-400 italic">—</div>
                        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.event_id">
                            <a :href="detailUrl(ev.inquiry.id)" @click.stop class="block p-1.5 text-[11px] rounded-lg border transition-all" :class="[eventClasses(ev.inquiry.status), ev.inquiry.type === 'pickup' ? 'border-dashed' : '']">
                                <div class="flex items-center gap-1"><span class="text-gray-500" x-text="fmtClock(ev.start)"></span><span x-show="ev.inquiry.type === 'pickup'" x-cloak class="text-[9px] font-bold uppercase text-cyan-700">Pickup</span></div>
                                <div class="text-gray-800 truncate font-medium" x-text="ev.inquiry.name"></div>
                                <div class="text-[10px] text-gray-600 truncate capitalize"><span x-text="jobKind(ev.inquiry)"></span> &middot; <span x-text="jobLabel(ev.inquiry)"></span></div>
                                <div x-show="ev.inquiry.address" x-cloak class="text-[10px] text-gray-400 truncate" x-text="ev.inquiry.address"></div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="totalOnCalendar === 0 && viewMode === 'day'" class="text-center text-gray-500 text-sm mt-4">No visits assigned to you yet.</div>
</div>
@endsection
