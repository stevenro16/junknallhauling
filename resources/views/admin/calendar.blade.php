@extends('layouts.admin')

@section('title', 'Calendar — '.config('business.name'))

@section('admin-content')
<div class="max-w-7xl mx-auto" x-data="calendar({ events: @js($events), detailBase: '{{ route('admin.inquiries.show', '__ID__') }}', initialView: 'day' })">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold">Pickup Calendar</h2>
            <p class="text-sm text-gray-500">Scheduled jobs by date</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                <template x-for="m in [{v:'day',l:'Day'},{v:'3day',l:'3 Day'},{v:'5day',l:'5 Day'}]" :key="m.v">
                    <button @click="viewMode = m.v" class="px-3 sm:px-4 py-1.5 transition-colors whitespace-nowrap" :class="viewMode === m.v ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="m.l"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-sm px-4 py-2">Today</button>
            <button @click="prev()" class="btn-outline p-2" aria-label="Previous"><x-icon name="chevron-left" class="w-5 h-5"/></button>
            <button @click="next()" class="btn-outline p-2" aria-label="Next"><x-icon name="chevron-right" class="w-5 h-5"/></button>
        </div>
    </div>

    <div class="text-center mb-6">
        <h2 class="text-xl sm:text-3xl font-black" x-text="headerLabel"></h2>
        <p class="text-sm text-gray-500 mt-1"><span x-text="totalOnCalendar"></span> active pickups on calendar</p>
    </div>

    {{-- Day view --}}
    @include('partials.admin.calendar-day')

    {{-- Multi-day (3-day / 5-day): columns on desktop, stacked agenda on mobile --}}
    <div x-show="viewMode === '3day' || viewMode === '5day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid gap-px bg-gray-200" :class="viewMode === '3day' ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-5'">
            <template x-for="(day, index) in rangeDays" :key="index">
                <div class="bg-white p-3 sm:min-h-[280px]" :class="isToday(day) && 'bg-amber-50'">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <button @click="goToDay(day)" class="text-left text-sm font-semibold hover:text-amber-600 transition-colors" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'">
                            <span x-text="day.toLocaleDateString(undefined, { weekday: 'short' })"></span>
                            <span class="text-gray-400 font-normal" x-text="day.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })"></span>
                        </button>
                        <span x-show="isToday(day)" class="text-[10px] uppercase font-bold tracking-wide text-amber-600 shrink-0">Today</span>
                    </div>
                    <div class="space-y-2">
                        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-xs text-gray-400 italic">No pickups</div>
                        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.id">
                            <a :href="detailUrl(ev.inquiry.id)" class="block p-2 text-xs rounded-lg border transition-all" :class="eventClasses(ev.inquiry.status)">
                                <div class="flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div><div class="font-mono text-amber-600 text-[10px]" x-text="ev.inquiry.ref"></div></div>
                                <div class="text-gray-800 truncate font-medium" x-text="ev.inquiry.name"></div>
                                <div class="text-gray-500 text-[10px] mt-0.5"><span x-text="fmtClock(ev.start)"></span> &ndash; <span x-text="fmtClock(ev.end)"></span></div>
                                <div x-show="ev.inquiry.assigned_employee" x-cloak class="flex items-center gap-0.5 text-[10px] text-amber-700 truncate"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
                                <div class="text-[10px] text-gray-500 capitalize" x-text="statusLabel(ev.inquiry.status)"></div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500">
        <div class="flex items-center gap-1.5"><div class="w-3 h-3 bg-brand-yellow/20 border border-brand-yellow/50 rounded"></div><span>Today</span></div>
        @foreach(['new'=>'New','left_voicemail'=>'Voicemail','reviewing'=>'Reviewing','quoted'=>'Quoted','scheduled'=>'Scheduled','service_performed'=>'Service Performed','completed'=>'Completed'] as $s => $lbl)
            <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full" :class="dotClass('{{ $s }}')"></div><span>{{ $lbl }}</span></div>
        @endforeach
        <div class="ml-auto text-gray-500"><span x-text="totalOnCalendar"></span> total on calendar</div>
    </div>
</div>
@endsection
