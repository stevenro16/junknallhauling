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
            @if(! empty($inquiry->photos))
                <div class="py-2.5">
                    <dt class="text-gray-500 mb-1">Customer Photos</dt>
                    <dd class="flex flex-wrap gap-2">
                        @foreach($inquiry->photos as $p)
                            <a href="{{ $p }}" target="_blank" rel="noopener"><img src="{{ $p }}" alt="Customer photo" class="max-h-40 rounded-lg border border-gray-200"></a>
                        @endforeach
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Travel & arrival — drive time from the field tech's current location to the job --}}
    @if($inquiry->address)
        <div class="mt-4 card-light p-5" x-data="etaEstimator({
                estimateUrl: '{{ route($routeBase.'.eta', $inquiry->id) }}',
                name: @js($inquiry->name),
                phone: @js($inquiry->phone),
                email: @js($inquiry->email),
                preferred: @js($inquiry->preferred_contact_method),
                businessName: @js(config('business.name')),
            })">
            <div class="text-sm font-semibold text-gray-800 mb-1">Travel &amp; Arrival</div>
            <p class="text-xs text-gray-500 mb-3">Calculate drive time from where you are now, then text/email the customer your ETA.</p>

            <button type="button" @click="calculate()" :disabled="loading" class="btn-outline text-sm py-2 px-4 inline-flex items-center gap-2 disabled:opacity-50">
                <x-icon name="map-pin" class="w-4 h-4"/>
                <span x-text="loading ? 'Locating…' : (calculated ? 'Recalculate from my location' : 'Calculate drive time')"></span>
            </button>

            <div x-show="calculated" x-cloak class="mt-3 space-y-3">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    <div><span class="text-gray-500">Distance:</span> <span class="font-medium text-gray-800" x-text="distanceMi != null ? distanceMi + ' mi' : '—'"></span></div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500">Drive time:</span>
                        <input type="number" min="0" x-model.number="travelMin" class="input-light text-sm py-1 w-16 text-center"><span class="text-gray-500">min</span>
                    </div>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-800">
                    Estimated arrival <span class="font-semibold" x-text="etaLabel || '—'"></span>
                </div>
                <button type="button" @click="communicate()" class="btn-primary text-sm py-2 px-4 inline-flex items-center gap-2">
                    <x-icon name="send" class="w-4 h-4"/> Send ETA to customer
                </button>
            </div>

            <p x-show="error" x-cloak class="text-xs text-red-600 mt-2" x-text="error"></p>
        </div>
    @endif

    {{-- Visit log: arrival & departure --}}
    <div class="mt-4 card-light p-5">
        <div class="text-sm font-semibold text-gray-800 mb-3">Visit Log</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach(['arrival' => ['Arrival', $inquiry->arrived_at, $inquiry->arrival_photos ?? []], 'departure' => ['Departure', $inquiry->departed_at, $inquiry->departure_photos ?? []]] as $which => $info)
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

                    {{-- Photos for this stamp (capture or upload, multiple) --}}
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        @if(count($info[2]))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach($info[2] as $idx => $p)
                                    <div class="relative">
                                        <a href="{{ $p }}" target="_blank" rel="noopener"><img src="{{ $p }}" alt="{{ $info[0] }} photo" class="w-16 h-16 object-cover rounded border border-gray-200"></a>
                                        <form method="POST" action="{{ route($routeBase.'.photo-remove', [$inquiry->id, $which]) }}" class="absolute -top-1.5 -right-1.5">
                                            @csrf<input type="hidden" name="index" value="{{ $idx }}">
                                            <button type="submit" class="w-5 h-5 flex items-center justify-center rounded-full bg-red-500 text-white leading-none shadow hover:bg-red-600" title="Remove photo">&times;</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" enctype="multipart/form-data" action="{{ route($routeBase.'.photo', [$inquiry->id, $which]) }}">
                            @csrf
                            <label class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 hover:text-amber-700 cursor-pointer">
                                <x-icon name="upload" class="w-3.5 h-3.5"/> Add photo
                                <input type="file" name="photos[]" accept="image/*" capture="environment" multiple class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Field actions — each requires a customer signature on a full-screen pad.
         Services show "Mark Service Performed"; equipment shows Delivered / Picked Up. --}}
    @php($isEquipment = $inquiry->service_type === 'equipment' || $inquiry->equipment_type)
    @php($signatures = $inquiry->signatures ?? [])
    @if(in_array($inquiry->status, ['scheduled', 'equipment_delivered', 'equipment_picked_up', 'service_performed', 'completed'], true))
        <div class="mt-4 card-light p-5" x-data="serviceSignature({ signUrl: '{{ route($routeBase.'.sign', $inquiry->id) }}' })">
            <div class="text-sm font-semibold text-gray-800 mb-1">Field Actions</div>
            <p class="text-xs text-gray-500 mb-3">Each action captures the customer's signature on a full-screen pad.</p>

            <div class="flex flex-wrap gap-2">
                @if($isEquipment)
                    @unless(in_array($inquiry->status, ['equipment_delivered', 'equipment_picked_up', 'completed'], true))
                        <button type="button" @click="openFor('equipment_delivered', 'Equipment Delivered')" class="btn-primary text-sm py-2.5 px-4 inline-flex items-center gap-2"><x-icon name="truck" class="w-4 h-4"/> Equipment Delivered</button>
                    @endunless
                    @if($inquiry->status === 'equipment_delivered')
                        <button type="button" @click="openFor('equipment_picked_up', 'Equipment Picked Up')" class="btn-primary text-sm py-2.5 px-4 inline-flex items-center gap-2"><x-icon name="truck" class="w-4 h-4"/> Equipment Picked Up</button>
                    @endif
                    @if($inquiry->status === 'equipment_picked_up')
                        <span class="inline-flex items-center gap-1.5 text-sm text-emerald-700"><x-icon name="check-circle" class="w-4 h-4"/> Equipment picked up</span>
                    @endif
                @else
                    @unless(in_array($inquiry->status, ['service_performed', 'completed'], true))
                        <button type="button" @click="openFor('service_performed', 'Service Performed')" class="btn-primary text-sm py-2.5 px-4 inline-flex items-center gap-2"><x-icon name="check" class="w-4 h-4"/> Mark Service Performed</button>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-sm text-emerald-700"><x-icon name="check-circle" class="w-4 h-4"/> Service performed</span>
                    @endunless
                @endif
            </div>

            {{-- Captured signatures — one per action --}}
            @if(count($signatures))
                <div class="mt-4 space-y-3">
                    @foreach($signatures as $key => $sig)
                        @php($sigLabel = ucwords(str_replace('_', ' ', $key)))
                        <div>
                            <div class="text-xs font-medium text-gray-700 flex items-center gap-1.5 flex-wrap">
                                <x-icon name="check-circle" class="w-3.5 h-3.5 text-emerald-500 shrink-0"/>
                                <span>{{ $sigLabel }}</span>
                                <span class="text-gray-400 font-normal">{{ \Carbon\Carbon::parse($sig['signed_at'])->format('D, M j · g:i A') }}</span>
                                <button type="button" @click="openFor('{{ $key }}', '{{ $sigLabel }}')" class="text-amber-600 hover:text-amber-700">Re-sign</button>
                            </div>
                            <img src="{{ $sig['signature'] }}" alt="{{ $sigLabel }} signature" class="mt-1 border border-gray-200 rounded-lg bg-white max-h-28 max-w-full">
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Full-screen signing pad --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-[120] bg-white flex flex-col" @keydown.escape.window="close()">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">{{ $inquiry->name ?: 'Customer' }} — sign for <span x-text="targetLabel"></span></div>
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
            @include('partials.admin.payment-link-panel', ['syncContact' => false, 'field' => true])
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
