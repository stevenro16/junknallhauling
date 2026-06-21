@extends('layouts.admin')

@section('title', $inquiry->ref.' — '.config('business.name'))

@php
    $statusLabel = ucwords(str_replace('_', ' ', $inquiry->status));
    $jobLabel = $inquiry->equipment_type
        ?: ucwords(str_replace('-', ' ', (string) $inquiry->service_type));
    $rental = ($inquiry->equipment_rental_duration && $inquiry->equipment_rental_unit)
        ? $inquiry->equipment_rental_duration.' '.$inquiry->equipment_rental_unit : null;
    $statusColors = [
        'new' => 'bg-blue-50 text-blue-700 border-blue-200',
        'reviewing' => 'bg-amber-50 text-amber-700 border-amber-200',
        'quoted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'scheduled' => 'bg-purple-50 text-purple-700 border-purple-200',
        'service_performed' => 'bg-teal-50 text-teal-700 border-teal-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
@endphp

@section('admin-content')
<div class="max-w-2xl mx-auto pb-24 sm:pb-8">
    <a href="{{ route('admin.my-schedule') }}" class="text-sm text-amber-600 hover:text-amber-700">&larr; Back to my schedule</a>

    @if(session('jobSaved'))
        <div class="mt-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg px-4 py-2 text-sm">&check; Status updated.</div>
    @endif
    @if(session('jobError'))
        <div class="mt-3 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-2 text-sm">{{ session('jobError') }}</div>
    @endif

    <div class="mt-4 card-light border-l-2 border-[#F8C820] p-5">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <span class="font-mono text-amber-700 text-sm tracking-widest bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">{{ $inquiry->ref }}</span>
                <h1 class="text-gray-900 text-2xl font-bold mt-1">{{ $inquiry->name ?: '(no name)' }}</h1>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0 {{ $statusColors[$inquiry->status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">{{ $statusLabel }}</span>
        </div>

        <dl class="divide-y divide-gray-100 text-sm">
            @if($inquiry->confirmed_date_time)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">When</dt><dd class="text-gray-900 font-medium text-right">{{ \Carbon\Carbon::parse($inquiry->confirmed_date_time)->format('D, M j · g:i A') }}</dd></div>
            @endif
            <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Job</dt><dd class="text-gray-900 text-right">{{ $jobLabel ?: '—' }}@if($rental) <span class="text-gray-400">· {{ $rental }}</span>@endif</dd></div>
            @if($inquiry->quoted_price)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Quoted Price</dt><dd class="text-gray-900 font-semibold text-right">${{ number_format((float) $inquiry->quoted_price, 2) }}</dd></div>
            @endif
            @if($inquiry->phone)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Phone</dt><dd class="text-right"><a href="tel:{{ $inquiry->phone }}" class="text-amber-600 font-medium">{{ $inquiry->phone }}</a></dd></div>
            @endif
            @if($inquiry->email)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Email</dt><dd class="text-gray-900 text-right break-all">{{ $inquiry->email }}</dd></div>
            @endif
            @if($inquiry->address)
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-gray-500">Address</dt>
                    <dd class="text-right">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($inquiry->address) }}" target="_blank" rel="noopener" class="text-amber-600 font-medium inline-flex items-center gap-1">{{ $inquiry->address }} <x-icon name="map" class="w-3.5 h-3.5"/></a>
                    </dd>
                </div>
            @endif
            @if($inquiry->preferred_day || $inquiry->preferred_time)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Preferred</dt><dd class="text-gray-900 text-right">{{ trim($inquiry->preferred_day.' '.$inquiry->preferred_time) ?: '—' }}</dd></div>
            @endif
            <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Preferred Contact</dt><dd class="text-gray-900 text-right capitalize">{{ $inquiry->preferred_contact_method ?: 'phone' }}</dd></div>
            <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">Payment</dt><dd class="text-right">@if($inquiry->payment_method)<span class="text-emerald-600 font-medium">{{ $inquiry->payment_method }}</span>@if($inquiry->payment_date)<span class="text-gray-400 text-xs"> · {{ $inquiry->payment_date }}</span>@endif @else<span class="text-gray-400">Not yet recorded</span>@endif</dd></div>
            @if($inquiry->description)
                <div class="py-2.5"><dt class="text-gray-500 mb-1">Customer Notes</dt><dd class="text-gray-800 whitespace-pre-wrap bg-gray-50 p-3 rounded-lg border border-gray-200">{{ $inquiry->description }}</dd></div>
            @endif
            @if($inquiry->admin_notes)
                <div class="py-2.5"><dt class="text-gray-500 mb-1">Service Notes</dt><dd class="text-gray-800 whitespace-pre-wrap bg-gray-50 p-3 rounded-lg border border-gray-200">{{ $inquiry->admin_notes }}</dd></div>
            @endif
            @if($inquiry->photo_base64)
                <div class="py-2.5">
                    <dt class="text-gray-500 mb-1">Customer Photo</dt>
                    <dd><a href="data:{{ $inquiry->photo_mime }};base64,{{ $inquiry->photo_base64 }}" target="_blank" rel="noopener"><img src="data:{{ $inquiry->photo_mime }};base64,{{ $inquiry->photo_base64 }}" alt="Customer-provided photo" class="max-h-64 rounded-lg border border-gray-200"></a></dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Visit log: arrival & departure --}}
    <div class="mt-4 card-light p-5">
        <div class="text-sm font-semibold text-gray-800 mb-3">Visit Log</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach(['arrival' => ['Arrival', $inquiry->arrived_at], 'departure' => ['Departure', $inquiry->departed_at]] as $which => $info)
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="text-xs uppercase tracking-wide text-gray-500">{{ $info[0] }}</div>
                    @if($info[1])
                        <div class="text-gray-900 text-lg font-semibold mt-0.5">{{ $info[1]->format('g:i A') }}</div>
                        <div class="text-[11px] text-gray-400">{{ $info[1]->format('D, M j') }}</div>
                        <form method="POST" action="{{ route('admin.my-schedule.time', [$inquiry->id, $which]) }}" class="mt-2">
                            @csrf<button type="submit" class="text-xs text-amber-600 hover:text-amber-700">Update to now</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.my-schedule.time', [$inquiry->id, $which]) }}" class="mt-2">
                            @csrf<button type="submit" class="w-full btn-outline py-2 text-sm">Record {{ strtolower($info[0]) }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Status action — employees mark service performed; completion stays with the office --}}
    @if($inquiry->status === 'scheduled')
        <div class="mt-4 card-light p-5">
            <div class="text-sm font-semibold text-gray-800 mb-3">Update status</div>
            <form method="POST" action="{{ route('admin.my-schedule.status', $inquiry->id) }}">
                @csrf
                <input type="hidden" name="status" value="service_performed">
                <button type="submit" class="w-full btn-primary py-3 text-sm">Mark Service Performed</button>
            </form>
        </div>
    @endif

    {{-- Customer signature — capturing it marks the job Service Performed (ready to bill) --}}
    @if($inquiry->service_signature || in_array($inquiry->status, ['scheduled', 'service_performed', 'completed'], true))
        <div class="mt-4 card-light p-5">
            <div class="text-sm font-semibold text-gray-800 mb-1">Customer Signature</div>
            @if($inquiry->service_signature)
                <p class="text-xs text-emerald-600 mb-2">&check; Signed{{ $inquiry->service_signed_at ? ' '.$inquiry->service_signed_at->format('D, M j · g:i A') : '' }}</p>
                <img src="{{ $inquiry->service_signature }}" alt="Customer signature" class="border border-gray-200 rounded-lg bg-white max-h-40">
            @else
                <p class="text-xs text-gray-500 mb-2">Have the customer sign below to confirm the service was performed.</p>
                <div x-data="serviceSignature({ signUrl: '{{ route('admin.my-schedule.sign', $inquiry->id) }}' })" x-init="initPad()">
                    <canvas x-ref="canvas" class="w-full h-44 border-2 border-dashed border-gray-300 rounded-lg bg-white touch-none cursor-crosshair"
                            @mousedown="start($event)" @mousemove="move($event)" @mouseup="end()" @mouseleave="end()"
                            @touchstart.prevent="start($event)" @touchmove.prevent="move($event)" @touchend.prevent="end()"></canvas>
                    <div class="flex items-center justify-between mt-2">
                        <button type="button" @click="clear()" class="text-xs text-gray-500 hover:text-gray-700">Clear</button>
                        <button type="button" @click="submit()" :disabled="submitting" class="btn-primary py-2 px-5 text-sm">
                            <span x-text="submitting ? 'Saving…' : 'Save signature'"></span>
                        </button>
                    </div>
                    <p x-show="error" x-cloak class="text-xs text-red-600 mt-1" x-text="error"></p>
                </div>
            @endif
        </div>
    @endif

    {{-- Notes & comments --}}
    <div class="mt-4 card-light p-5">
        @include('partials.admin.comment-thread', ['postUrl' => route('admin.my-schedule.comment', $inquiry->id), 'comments' => $comments])
    </div>
</div>
@endsection
