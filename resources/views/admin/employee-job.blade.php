@extends('layouts.admin')

@section('title', $inquiry->ref.' — '.config('business.name'))

@php
    // Defaults keep the employee usage unchanged; the admin Field View overrides these.
    $routeBase = $routeBase ?? 'admin.my-schedule';
    $backRoute = $backRoute ?? 'admin.my-schedule';
    $backLabel = $backLabel ?? 'Back to my schedule';
    $adminField = $adminField ?? false;

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
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route($backRoute) }}" class="text-sm text-amber-600 hover:text-amber-700">&larr; {{ $backLabel }}</a>
        @if($adminField)
            <a href="{{ route('admin.inquiries.show', $inquiry->id) }}" class="btn-outline text-xs py-1.5 px-3 inline-flex items-center gap-1.5 shrink-0">
                <x-icon name="file-text" class="w-3.5 h-3.5"/> Open full quote
            </a>
        @endif
    </div>

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
                        <form method="POST" action="{{ route($routeBase.'.time', [$inquiry->id, $which]) }}" class="mt-2">
                            @csrf<button type="submit" class="text-xs text-amber-600 hover:text-amber-700">Update to now</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route($routeBase.'.time', [$inquiry->id, $which]) }}" class="mt-2">
                            @csrf<button type="submit" class="w-full btn-outline py-2 text-sm">Record {{ strtolower($info[0]) }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Customer signature — capturing it marks the job Service Performed (ready to bill).
         Signing happens on a full-screen pad; a saved signature can be re-done. --}}
    @if($inquiry->service_signature || in_array($inquiry->status, ['scheduled', 'service_performed', 'completed'], true))
        <div class="mt-4 card-light p-5" x-data="serviceSignature({ signUrl: '{{ route($routeBase.'.sign', $inquiry->id) }}' })">
            <div class="text-sm font-semibold text-gray-800 mb-1">Customer Signature</div>
            @if($inquiry->service_signature)
                <p class="text-xs text-emerald-600 mb-2">&check; Signed{{ $inquiry->service_signed_at ? ' '.$inquiry->service_signed_at->format('D, M j · g:i A') : '' }}</p>
                <img src="{{ $inquiry->service_signature }}" alt="Customer signature" class="border border-gray-200 rounded-lg bg-white max-h-40">
                <div class="mt-3">
                    <button type="button" @click="openPad()" class="btn-outline text-sm py-2 px-4 inline-flex items-center gap-2">
                        <x-icon name="pencil" class="w-4 h-4"/> Re-sign
                    </button>
                </div>
            @else
                <p class="text-xs text-gray-500 mb-3">Have the customer sign to confirm the service was performed.</p>
                <button type="button" @click="openPad()" class="btn-primary py-2.5 px-5 text-sm inline-flex items-center gap-2">
                    <x-icon name="pencil" class="w-4 h-4"/> Open signature pad
                </button>
            @endif

            {{-- Full-screen signing pad (lots of room for the customer; can clear & redo) --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-[120] bg-white flex flex-col" @keydown.escape.window="close()">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">{{ $inquiry->name ?: 'Customer' }} — sign below</div>
                        <div class="text-xs text-gray-500">{{ $inquiry->ref }}</div>
                    </div>
                    <button type="button" @click="close()" class="text-gray-400 hover:text-gray-700" aria-label="Cancel"><x-icon name="x" class="w-6 h-6"/></button>
                </div>
                <div class="flex-1 p-3 min-h-0">
                    <canvas x-ref="canvas" class="w-full h-full border-2 border-dashed border-gray-300 rounded-lg bg-white touch-none cursor-crosshair"
                            @mousedown="start($event)" @mousemove="move($event)" @mouseup="end()" @mouseleave="end()"
                            @touchstart.prevent="start($event)" @touchmove.prevent="move($event)" @touchend.prevent="end()"></canvas>
                </div>
                <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-gray-200 shrink-0">
                    <button type="button" @click="clear()" class="btn-outline text-sm py-2 px-4">Clear</button>
                    <p x-show="error" x-cloak class="text-xs text-red-600 flex-1 text-center" x-text="error"></p>
                    <button type="button" @click="submit()" :disabled="submitting" class="btn-primary py-2.5 px-6 text-sm">
                        <span x-text="submitting ? 'Saving…' : 'Save signature'"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment — admin Field View only: generate / send the payment link --}}
    @if($adminField)
        <div class="mt-4">
            @include('partials.admin.payment-link-panel', ['syncContact' => false])
        </div>
    @endif

    {{-- Status action — sits under the payment link; mark the service performed --}}
    @if($inquiry->status === 'scheduled')
        <div class="mt-4 card-light p-5">
            <div class="text-sm font-semibold text-gray-800 mb-3">Update status</div>
            <form method="POST" action="{{ route($routeBase.'.status', $inquiry->id) }}">
                @csrf
                <input type="hidden" name="status" value="service_performed">
                <button type="submit" class="w-full btn-primary py-3 text-sm">Mark Service Performed</button>
            </form>
        </div>
    @endif

    {{-- Notes & comments --}}
    <div class="mt-4 card-light p-5">
        @include('partials.admin.comment-thread', ['postUrl' => route($routeBase.'.comment', $inquiry->id), 'comments' => $comments])
    </div>

    {{-- Quote status — shown to everyone; only the admin Field View can change it manually --}}
    <div class="mt-4 card-light p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-semibold text-gray-800">Quote Status</div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0 {{ $statusColors[$inquiry->status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">{{ $statusLabel }}</span>
        </div>
        @if($adminField)
            <form method="POST" action="{{ route($routeBase.'.status', $inquiry->id) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                @csrf
                <select name="status" class="input-light flex-1">
                    @foreach(config('business.status_labels') as $key => $label)
                        <option value="{{ $key }}" @selected($inquiry->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary py-2 px-5 text-sm shrink-0">Update Status</button>
            </form>
            <p class="text-xs text-gray-400 mt-2">Manually set the quote status.</p>
        @else
            <p class="text-xs text-gray-400 mt-2">Status is managed by the office.</p>
        @endif
    </div>
</div>
@endsection
