<!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    @php
        // yieldContent() returns HTML-escaped content (inline @section values and
        // defaults are e()'d by Blade), so emit with {!! !!} to avoid double-escaping.
        $metaTitle = trim($__env->yieldContent('title', config('business.name').' | Junk Removal & Dumpster Rentals'));
        $metaDescription = trim($__env->yieldContent('description', 'Professional junk removal and dumpster rental serving Yucaipa, Redlands, Beaumont, Highland and the Inland Empire. Same-day service available. Call '.config('business.phone').' for a free quote.'));
    @endphp
    <title>{!! $metaTitle !!}</title>
    <meta name="description" content="{!! $metaDescription !!}">
    <link rel="canonical" href="{{ url()->current() }}">
    @hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('business.name') }}">
    <meta property="og:title" content="{!! $metaTitle !!}">
    <meta property="og:description" content="{!! $metaDescription !!}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/trailer.jpg') }}">
    <meta property="og:image:width" content="808">
    <meta property="og:image:height" content="419">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $metaTitle !!}">
    <meta name="twitter:description" content="{!! $metaDescription !!}">
    <meta name="twitter:image" content="{{ asset('images/trailer.jpg') }}">
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-white text-slate-900">
    @include('partials.navbar')
    <main class="flex-1">@yield('content')</main>
    @include('partials.footer')
    @stack('scripts')
</body>
</html>
