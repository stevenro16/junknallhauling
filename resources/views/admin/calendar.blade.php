@extends('layouts.admin')

@section('title', 'Calendar — '.config('business.name'))

@section('admin-content')
<div class="max-w-7xl mx-auto" x-data="calendar({ events: @js($events), detailBase: '{{ route('admin.inquiries.show', '__ID__') }}' })">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold">Pickup Calendar</h2>
            <p class="text-sm text-gray-500">Scheduled jobs by date</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                <template x-for="mode in ['month','week','day']" :key="mode">
                    <button @click="viewMode = mode" class="px-4 py-1.5 transition-colors capitalize" :class="viewMode === mode ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="mode"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-sm px-4 py-2">Today</button>
            <button @click="prev()" class="btn-outline p-2" aria-label="Previous"><x-icon name="chevron-left" class="w-5 h-5"/></button>
            <button @click="next()" class="btn-outline p-2" aria-label="Next"><x-icon name="chevron-right" class="w-5 h-5"/></button>
        </div>
    </div>

    <div class="text-center mb-6">
        <h2 class="text-3xl font-black" x-text="headerLabel"></h2>
        <p class="text-sm text-gray-500 mt-1"><span x-text="totalOnCalendar"></span> active pickups on calendar</p>
    </div>

    {{-- Day view --}}
    <div x-show="viewMode === 'day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="overflow-y-auto max-h-[75vh]">
            <div class="flex h-[1088px]">
                <div class="w-16 shrink-0 grid grid-rows-[repeat(17,4rem)] bg-white border-r border-gray-200 z-10">
                    <template x-for="hour in HOURS" :key="hour">
                        <div class="flex items-start justify-end pr-2 pt-0.5 border-b border-gray-200/60">
                            <span class="text-[11px] text-gray-500 -translate-y-2.5 select-none whitespace-nowrap" x-text="formatHour(hour)"></span>
                        </div>
                    </template>
                </div>
                <div class="flex-1 relative">
                    <div class="absolute inset-0 grid grid-rows-[repeat(17,4rem)]">
                        <template x-for="hour in HOURS" :key="hour"><div class="relative border-b border-gray-200/60"><div class="absolute inset-x-0 top-1/2 border-b border-gray-100"></div></div></template>
                    </div>
                    <div x-show="dayLayout.length === 0" class="absolute inset-0 flex items-center justify-center"><p class="text-gray-500 text-sm">No pickups on this day</p></div>
                    <template x-for="ev in dayLayout" :key="ev.inquiry.id">
                        <a :href="detailUrl(ev.inquiry.id)" :style="ev.style" class="rounded-lg border transition-all overflow-hidden px-2 py-1 flex flex-col" :class="eventClasses(ev.inquiry.status)">
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div>
                                <div class="font-mono text-amber-600 text-[10px] leading-none truncate" x-text="ev.inquiry.ref"></div>
                            </div>
                            <div class="font-semibold text-sm text-gray-900 leading-tight truncate" x-text="ev.inquiry.name"></div>
                            <div class="text-gray-500 text-[10px] leading-tight"><span x-text="fmtClock(ev.start)"></span> &ndash; <span x-text="fmtClock(ev.end)"></span></div>
                            <div x-show="ev.big" class="text-gray-400 text-[10px] leading-tight truncate capitalize"><span x-text="statusLabel(ev.inquiry.status)"></span> &middot; <span x-text="serviceLabel(ev.inquiry.service_type)"></span></div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Week + Month --}}
    <div x-show="viewMode !== 'day'" class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div class="p-3 text-center text-sm font-medium text-gray-500 border-r border-gray-200 last:border-r-0">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Week --}}
        <div x-show="viewMode === 'week'" class="grid grid-cols-7">
            <template x-for="(day, index) in weekDays" :key="index">
                <div @click="eventsForKey(dayKey(day)).length > 0 && goToDay(day)" class="min-h-[220px] p-3 border-r border-gray-200 last:border-r-0 cursor-pointer hover:bg-gray-50 transition-colors" :class="isToday(day) && 'bg-amber-50'">
                    <div class="text-sm font-medium mb-2" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })"></div>
                    <div class="space-y-2">
                        <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-xs text-gray-600 italic">No pickups</div>
                        <template x-for="ev in eventsForKey(dayKey(day))" :key="ev.inquiry.id">
                            <a :href="detailUrl(ev.inquiry.id)" @click.stop class="block p-2 text-xs rounded-lg border transition-all" :class="eventClasses(ev.inquiry.status)">
                                <div class="flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div><div class="font-mono text-amber-600 text-[10px]" x-text="ev.inquiry.ref"></div></div>
                                <div class="text-gray-800 truncate font-medium" x-text="ev.inquiry.name"></div>
                                <div class="text-gray-500 text-[10px] mt-0.5"><span x-text="fmtClock(ev.start)"></span> &ndash; <span x-text="fmtClock(ev.end)"></span></div>
                                <div class="text-[10px] text-gray-500 capitalize" x-text="statusLabel(ev.inquiry.status)"></div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Month --}}
        <div x-show="viewMode === 'month'" x-cloak class="grid grid-cols-7 bg-white">
            <template x-for="(day, index) in monthGrid" :key="index">
                <div>
                    <template x-if="!day"><div class="min-h-[110px] border-r border-b border-gray-200 bg-gray-50"></div></template>
                    <template x-if="day">
                        <div @click="goToDay(day)" class="min-h-[110px] p-2 border-r border-b border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors" :class="isToday(day) && 'bg-amber-50'">
                            <div class="text-sm font-medium mb-1.5" :class="isToday(day) ? 'text-amber-600' : 'text-gray-700'" x-text="day.getDate()"></div>
                            <div class="space-y-1">
                                <div x-show="eventsForKey(dayKey(day)).length === 0" class="text-[10px] text-gray-600 italic">No pickups</div>
                                <template x-for="ev in eventsForKey(dayKey(day)).slice(0, 2)" :key="ev.inquiry.id">
                                    <div class="text-[10px] px-1.5 py-0.5 rounded flex items-center gap-1 border truncate" :class="eventClasses(ev.inquiry.status)">
                                        <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div>
                                        <span class="text-gray-800 truncate" x-text="ev.inquiry.name"></span>
                                    </div>
                                </template>
                                <div x-show="eventsForKey(dayKey(day)).length > 2" class="text-[10px] text-gray-500 pl-1">+<span x-text="eventsForKey(dayKey(day)).length - 2"></span> more</div>
                            </div>
                        </div>
                    </template>
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
