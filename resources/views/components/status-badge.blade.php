@props(['status'])

@php
    $labels = config('business.status_labels');
    $classMap = [
        'new'               => 'status-new',
        'left_voicemail'    => 'status-reviewing',
        'reviewing'         => 'status-reviewing',
        'quoted'            => 'status-quoted',
        'scheduled'         => 'status-scheduled',
        'service_performed' => 'status-service_performed',
        'completed'         => 'status-completed',
        'cancelled'         => 'status-cancelled',
    ];
    $label = $labels[$status] ?? 'New';
    $cls   = $classMap[$status] ?? 'status-new';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border $cls"]) }}>{{ $label }}</span>
