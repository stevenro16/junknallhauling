@extends('layouts.bare')

@section('title', 'Day Calendar — '.config('business.name'))

@section('content')
<div class="min-h-screen bg-gray-100 p-3"
     x-data="calendar({
        events: @js($events),
        detailBase: '{{ route('admin.inquiries.show', '__ID__') }}',
        initialView: 'day',
        initialDate: @js($date),
     })">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-base font-bold text-gray-900" x-text="headerLabel"></h2>
        <div class="flex items-center gap-2">
            <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
            <button @click="prev()" class="btn-outline p-1.5" aria-label="Previous day"><x-icon name="chevron-left" class="w-4 h-4"/></button>
            <button @click="next()" class="btn-outline p-1.5" aria-label="Next day"><x-icon name="chevron-right" class="w-4 h-4"/></button>
        </div>
    </div>

    {{-- Pick hint / confirmation --}}
    <div x-show="pickedMinutes === null" class="mb-3 text-xs text-gray-500">Click a time on the calendar to schedule this quote.</div>
    <div x-show="pickedMinutes !== null" x-cloak class="mb-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
        &check; Visit set to <span class="font-semibold" x-text="pickedLabelFull"></span>. Close this calendar to return to the quote.
    </div>

    @include('partials.admin.calendar-day', ['linkTarget' => '_top', 'pickMode' => true])
</div>
@endsection
