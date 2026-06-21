@extends('layouts.admin')

@php
    // Defaults keep the employee "My Schedule" usage unchanged; the admin Field View
    // overrides these (see FieldViewController).
    $title = $title ?? 'My Schedule';
    $subtitle = $subtitle ?? 'Your assigned visits';
    $detailRoute = $detailRoute ?? 'admin.my-schedule.job';
    $unitNoun = $unitNoun ?? 'assigned visit';
    $emptyText = $emptyText ?? 'No visits assigned to you yet.';
@endphp

@section('title', $title.' — '.config('business.name'))

@section('admin-content')
<div class="w-full" x-data="calendar({ events: @js($events), detailBase: '{{ route($detailRoute, '__ID__') }}', initialView: 'day' })">
    {{-- Header --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold">{{ $title }}</h2>
            <p class="text-sm text-gray-500">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                <template x-for="m in [{v:'day',l:'Day'},{v:'3day',l:'3 Day'},{v:'week',l:'Week'}]" :key="m.v">
                    <button @click="viewMode = m.v" class="px-3 sm:px-4 py-1.5 transition-colors whitespace-nowrap" :class="viewMode === m.v ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'" x-text="m.l"></button>
                </template>
            </div>
            <button @click="today()" class="btn-outline text-sm px-4 py-2">Today</button>
            <button @click="prev()" class="btn-outline p-2" aria-label="Previous"><x-icon name="chevron-left" class="w-5 h-5"/></button>
            <button @click="next()" class="btn-outline p-2" aria-label="Next"><x-icon name="chevron-right" class="w-5 h-5"/></button>
        </div>
    </div>

    <div class="text-center mb-5">
        <h2 class="text-2xl font-black" x-text="headerLabel"></h2>
        <p class="text-sm text-gray-500 mt-1"><span x-text="totalOnCalendar"></span> {{ $unitNoun }}<span x-show="totalOnCalendar !== 1">s</span></p>
    </div>

    {{-- Day view --}}
    @include('partials.admin.calendar-day')

    {{-- 3-day view: columns on desktop, stacked on mobile --}}
    <div x-show="viewMode === '3day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid gap-px bg-gray-200 grid-cols-1 sm:grid-cols-3">
            <template x-for="(day, index) in rangeDays" :key="index">
                @include('partials.admin.my-schedule-day-cell')
            </template>
        </div>
    </div>

    {{-- Week view: columns on desktop, stacked on mobile --}}
    <div x-show="viewMode === 'week'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid gap-px bg-gray-200 grid-cols-1 sm:grid-cols-7">
            <template x-for="(day, index) in weekDays" :key="index">
                @include('partials.admin.my-schedule-day-cell')
            </template>
        </div>
    </div>

    <div x-show="totalOnCalendar === 0 && viewMode === 'day'" class="text-center text-gray-500 text-sm mt-4">{{ $emptyText }}</div>
</div>
@endsection
