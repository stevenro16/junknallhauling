@extends('layouts.admin')

@section('title', 'End of Day Report — '.config('business.name'))

@php
    $statusColors = [
        'new' => 'bg-blue-50 text-blue-700 border-blue-200',
        'left_voicemail' => 'bg-rose-50 text-rose-700 border-rose-200',
        'reviewing' => 'bg-amber-50 text-amber-700 border-amber-200',
        'quoted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'scheduled' => 'bg-purple-50 text-purple-700 border-purple-200',
        'service_performed' => 'bg-teal-50 text-teal-700 border-teal-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
    $jobLabel = fn ($v) => $v->equipment_type ?: ucwords(str_replace('-', ' ', (string) $v->service_type));
    $rental = fn ($v) => ($v->equipment_rental_duration && $v->equipment_rental_unit) ? $v->equipment_rental_duration.' '.$v->equipment_rental_unit : null;
    $onsite = function ($v) {
        if (! $v->arrived_at || ! $v->departed_at) return null;
        $m = $v->arrived_at->diffInMinutes($v->departed_at);
        return intdiv($m, 60).'h '.($m % 60).'m';
    };
    $completed = $visits->where('status', 'completed')->count();
    $recorded = $visits->filter(fn ($v) => $v->arrived_at)->count();
    $quotedTotal = $visits->sum('quoted_price');
@endphp

@section('admin-content')
<div class="max-w-6xl mx-auto">

    {{-- Controls (hidden when printing) --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5 print:hidden">
        <div>
            <h2 class="text-2xl font-semibold">End of Day Report</h2>
            <p class="text-sm text-gray-500">Visits scheduled for the selected day</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.eod-report', ['date' => $prevDate]) }}" class="btn-outline p-2" aria-label="Previous day"><x-icon name="chevron-left" class="w-5 h-5"/></a>
            <form method="GET" action="{{ route('admin.eod-report') }}">
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()" class="input-light py-2">
            </form>
            <a href="{{ route('admin.eod-report', ['date' => $nextDate]) }}" class="btn-outline p-2" aria-label="Next day"><x-icon name="chevron-right" class="w-5 h-5"/></a>
            <a href="{{ route('admin.eod-report', ['date' => $today]) }}" class="btn-outline text-sm px-3 py-2">Today</a>
            <button type="button" onclick="window.print()" class="btn-primary text-sm px-3 py-2 inline-flex items-center gap-1"><x-icon name="file-text" class="w-4 h-4"/> Print</button>
        </div>
    </div>

    {{-- Print header --}}
    <div class="hidden print:block mb-4">
        <div class="text-lg font-bold">{{ config('business.name') }} — End of Day Report</div>
        <div class="text-sm text-gray-600">{{ $date->format('l, F j, Y') }} · {{ $visits->count() }} visit{{ $visits->count() === 1 ? '' : 's' }}</div>
    </div>

    <div class="text-xl font-bold mb-4 print:hidden">{{ $date->format('l, F j, Y') }}</div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Visits</div><div class="text-3xl font-black text-purple-600 mt-1">{{ $visits->count() }}</div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Completed</div><div class="text-3xl font-black text-emerald-600 mt-1">{{ $completed }}</div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Quoted Total</div><div class="text-3xl font-black text-emerald-600 mt-1">${{ number_format((float) $quotedTotal, 2) }}</div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Visits Recorded</div><div class="text-3xl font-black text-teal-600 mt-1">{{ $recorded }}<span class="text-base text-gray-400">/{{ $visits->count() }}</span></div></div>
    </div>

    @if($visits->isEmpty())
        <div class="card-light p-10 text-center text-gray-400">No visits scheduled for this day.</div>
    @else
        {{-- Desktop table --}}
        <div class="card-light overflow-hidden hidden md:block print:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-3 py-3">Time</th>
                            <th class="text-left px-3 py-3">Status</th>
                            <th class="text-left px-3 py-3">Customer</th>
                            <th class="text-left px-3 py-3">Service / Equipment</th>
                            <th class="text-right px-3 py-3">Quoted</th>
                            <th class="text-left px-3 py-3">On-Site</th>
                            <th class="text-center px-3 py-3">Signature</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($visits as $v)
                            <tr class="align-top cursor-pointer hover:bg-amber-50/40 print:cursor-auto print:hover:bg-transparent" onclick="window.location='{{ route('admin.inquiries.show', $v->id) }}'">
                                <td class="px-3 py-3 whitespace-nowrap font-medium text-gray-800">{{ \Carbon\Carbon::parse($v->confirmed_date_time)->format('g:i A') }}</td>
                                <td class="px-3 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusColors[$v->status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">{{ ucwords(str_replace('_', ' ', $v->status)) }}</span></td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">{{ $v->name ?: '(no name)' }}</div>
                                    <div class="text-xs text-gray-500">{{ $v->phone }}</div>
                                    @if($v->address)<div class="text-xs text-gray-500">{{ $v->address }}</div>@endif
                                    @if($v->assignedEmployee)<div class="text-xs text-amber-700 mt-0.5">Tech: {{ $v->assignedEmployee->username }}</div>@endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-gray-800 capitalize">{{ $jobLabel($v) ?: '—' }}</div>
                                    @if($rental($v))<div class="text-xs text-gray-500">{{ $rental($v) }}</div>@endif
                                </td>
                                <td class="px-3 py-3 text-right whitespace-nowrap font-semibold text-emerald-600">{{ $v->quoted_price ? '$'.number_format((float) $v->quoted_price, 2) : '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs">
                                    @if($v->arrived_at || $v->departed_at)
                                        <div><span class="text-gray-400">In:</span> {{ $v->arrived_at ? $v->arrived_at->format('g:i A') : '—' }}</div>
                                        <div><span class="text-gray-400">Out:</span> {{ $v->departed_at ? $v->departed_at->format('g:i A') : '—' }}</div>
                                        @if($onsite($v))<div class="font-semibold text-gray-700 mt-0.5">{{ $onsite($v) }}</div>@endif
                                    @else
                                        <span class="text-gray-300">Not recorded</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($v->service_signature)
                                        <img src="{{ $v->service_signature }}" alt="Signature" class="inline-block h-12 border border-gray-200 rounded bg-white">
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden print:hidden space-y-3">
            @foreach($visits as $v)
                <a href="{{ route('admin.inquiries.show', $v->id) }}" class="card-light p-4 block active:bg-amber-50/60">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($v->confirmed_date_time)->format('g:i A') }} · {{ $v->name ?: '(no name)' }}</div>
                            <div class="text-xs text-gray-500">{{ $v->phone }}</div>
                            @if($v->address)<div class="text-xs text-gray-500">{{ $v->address }}</div>@endif
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0 {{ $statusColors[$v->status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">{{ ucwords(str_replace('_', ' ', $v->status)) }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        <span class="text-gray-700 capitalize">{{ $jobLabel($v) ?: '—' }}@if($rental($v)) <span class="text-gray-400">· {{ $rental($v) }}</span>@endif</span>
                        @if($v->quoted_price)<span class="font-semibold text-emerald-600">${{ number_format((float) $v->quoted_price, 2) }}</span>@endif
                    </div>
                    @if($v->assignedEmployee)<div class="mt-1 text-xs text-amber-700">Tech: {{ $v->assignedEmployee->username }}</div>@endif
                    @if($v->arrived_at || $v->departed_at)
                        <div class="mt-2 text-xs text-gray-600 flex flex-wrap gap-x-4">
                            <span><span class="text-gray-400">In:</span> {{ $v->arrived_at?->format('g:i A') ?: '—' }}</span>
                            <span><span class="text-gray-400">Out:</span> {{ $v->departed_at?->format('g:i A') ?: '—' }}</span>
                            @if($onsite($v))<span class="font-semibold text-gray-700">On-site {{ $onsite($v) }}</span>@endif
                        </div>
                    @endif
                    @if($v->service_signature)
                        <div class="mt-2"><div class="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Customer Signature</div><img src="{{ $v->service_signature }}" alt="Signature" class="h-14 border border-gray-200 rounded bg-white"></div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
