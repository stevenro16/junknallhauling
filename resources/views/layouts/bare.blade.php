<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('business.name'))</title>
    <link rel="icon" href="/favicon.jpg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-charcoal-900 text-gray-100 min-h-screen antialiased">
    @yield('content')
</body>
</html>
