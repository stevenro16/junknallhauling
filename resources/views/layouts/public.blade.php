<!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    <title>@yield('title', config('business.name').' | Junk Removal & Dumpster Rentals')</title>
    <meta name="description" content="@yield('description', 'Professional junk removal and dumpster rental serving Yucaipa, Redlands, Beaumont, Highland and the Inland Empire. Same-day service available. Call '.config('business.phone').' for a free quote.')">
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
