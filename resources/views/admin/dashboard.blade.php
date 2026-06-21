@extends('layouts.admin')

@section('title', 'Admin Dashboard — '.config('business.name'))

@section('admin-content')
<div class="w-full">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">
            @switch($section)
                @case('stats') Analytics &amp; Stats @break
                @case('services') Service Catalog @break
                @case('equipment') Equipment Catalog @break
                @case('content') Site Content @break
                @case('admins') Account Management @break
                @default Quotes
            @endswitch
        </h2>
    </div>

    @switch($section)
        @case('stats') @include('partials.admin.analytics-section') @break
        @case('services') @include('partials.admin.services-section') @break
        @case('equipment') @include('partials.admin.equipment-section') @break
        @case('content') @include('partials.admin.content-section') @break
        @case('admins') @include('partials.admin.admins-section') @break
        @default @include('partials.admin.inquiries-section')
    @endswitch
</div>
@endsection
