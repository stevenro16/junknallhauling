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
     })">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-base font-bold text-gray-900" x-text="headerLabel"></h2>
        <div class="flex items-center gap-2">
            <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
            <button @click="prev()" class="btn-outline p-1.5" aria-label="Previous day"><x-icon name="chevron-left" class="w-4 h-4"/></button>
            <button @click="next()" class="btn-outline p-1.5" aria-label="Next day"><x-icon name="chevron-right" class="w-4 h-4"/></button>
        </div>
    </div>

    {{-- Hint / placed status --}}
    <div x-show="pickedMinutes === null" class="mb-3 text-xs text-gray-500">Click a time to place this visit. Drag it to reschedule, or drag its bottom edge to change the duration.</div>
    <div x-show="pickedMinutes !== null" x-cloak class="mb-3 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        <span class="font-semibold" x-text="pickedLabelFull"></span> — drag to move, drag the bottom edge to resize.
    </div>

    @include('partials.admin.calendar-day', ['linkTarget' => '_top', 'pickMode' => true])

    {{-- Save Schedule — bottom-right corner of the calendar --}}
    <button type="button" x-show="pickedMinutes !== null" x-cloak @click="applyToQuote()"
            class="fixed bottom-4 right-4 z-50 btn-primary text-sm py-3 px-6 rounded-full shadow-lg">
        Save Schedule
    </button>
</div>
@endsection
