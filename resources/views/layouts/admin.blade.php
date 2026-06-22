<!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    <title>@yield('title', 'Admin — '.config('business.name'))</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full">
<div x-data="adminShell({
        changePwdUrl: '{{ route('admin.change-password.update') }}',
        meUrl: '{{ route('admin.me.update') }}',
        logoutUrl: '{{ route('admin.logout') }}',
        loginUrl: '{{ route('admin.login') }}',
        currentUsername: @js(session('admin_username')),
    })" class="flex h-screen overflow-hidden bg-gray-200 text-gray-800 print:block print:h-auto print:overflow-visible print:bg-white">

    {{-- Desktop sidebar --}}
    <div class="hidden lg:block h-full print:hidden" x-data="{ navExpanded: $persist(false).as('admin_nav_expanded') }">
        @include('partials.admin.sidebar', ['collapsible' => true])
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden print:overflow-visible">
        {{-- Mobile top nav (mirrors the public site: logo left, hamburger top-right, dropdown panel) --}}
        <div class="lg:hidden shrink-0 print:hidden" @keydown.escape.window="mobileOpen = false">
            <div class="bg-charcoal-800 border-b border-white/10 flex h-14 items-center justify-between px-4">
                <a href="{{ route('home') }}" class="flex items-center" title="Back to main website">
                    <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" width="160" height="42"
                         class="h-8 w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
                </a>
                <button type="button" @click="mobileOpen = !mobileOpen"
                        class="inline-flex items-center justify-center w-10 h-10 -mr-2 text-slate-200 hover:text-[#F8C820] transition-colors"
                        aria-label="Toggle navigation menu" :aria-expanded="mobileOpen">
                    <x-icon name="menu" class="w-6 h-6" x-show="!mobileOpen"/>
                    <x-icon name="x" class="w-6 h-6" x-show="mobileOpen" x-cloak/>
                </button>
            </div>
            <div x-show="mobileOpen" x-cloak x-transition
                 class="border-t border-white/10 bg-charcoal-800 max-h-[70vh] overflow-y-auto">
                @include('partials.admin.sidebar', ['collapsible' => false, 'mobile' => true])
            </div>
        </div>
        <div class="flex-1 overflow-auto px-4 pt-4 lg:px-6 lg:pt-6 print:overflow-visible print:p-0 {{ session('admin_role') === 'admin' ? 'pb-24 lg:pb-6' : 'pb-4' }}">
            @yield('admin-content')
        </div>
    </div>

    {{-- Mobile bottom toolbar (admin quick-jump; configurable in Site Content) --}}
    @include('partials.admin.mobile-toolbar')

    {{-- Change Password Modal --}}
    <div x-show="showPwd" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" @click.self="showPwd = false">
        <div class="w-full max-w-md bg-white rounded-xl border border-gray-200 shadow-xl p-6 relative">
            <button @click="showPwd = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><x-icon name="x" class="w-5 h-5"/></button>
            <h3 class="text-xl font-semibold text-gray-800 mb-1">Change Your Password</h3>
            <p class="text-sm text-gray-500 mb-6">Update your admin password. You will stay logged in.</p>
            <form @submit.prevent="submitPwd()" class="space-y-4">
                <div><label class="block text-sm text-gray-700 mb-1.5">Current Password</label><input type="password" x-model="pwd.current" class="input-light" required autocomplete="current-password"></div>
                <div><label class="block text-sm text-gray-700 mb-1.5">New Password</label><input type="password" x-model="pwd.next" class="input-light" required minlength="6" autocomplete="new-password"></div>
                <div><label class="block text-sm text-gray-700 mb-1.5">Confirm New Password</label><input type="password" x-model="pwd.confirm" class="input-light" required minlength="6" autocomplete="new-password"></div>
                <p x-show="pwd.error" x-text="pwd.error" class="text-red-500 text-sm" x-cloak></p>
                <p x-show="pwd.success" class="text-green-600 text-sm" x-cloak>&check; Password changed successfully. Closing...</p>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showPwd = false" class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="submit" :disabled="pwd.loading" class="btn-primary flex-1 py-2.5 text-sm"><span x-text="pwd.loading ? 'Updating...' : 'Change Password'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Account Settings Modal --}}
    <div x-show="showAccount" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" @click.self="showAccount = false">
        <div class="w-full max-w-md bg-white rounded-xl border border-gray-200 shadow-xl p-6 relative">
            <button @click="showAccount = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><x-icon name="x" class="w-5 h-5"/></button>
            <h3 class="text-xl font-semibold text-gray-800 mb-1">Account Settings</h3>
            <p class="text-sm text-gray-500 mb-6">Update your account details.</p>
            <form @submit.prevent="submitAccount()" class="space-y-4">
                <div><label class="block text-sm text-gray-700 mb-1.5">Username</label><input type="text" x-model="account.username" class="input-light" autocomplete="username"></div>
                <p x-show="account.error" x-text="account.error" class="text-red-500 text-sm" x-cloak></p>
                <p x-show="account.success" class="text-green-600 text-sm" x-cloak>&check; Account updated. Closing...</p>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showAccount = false" class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="submit" :disabled="account.loading" class="flex-1 py-2.5 text-sm rounded-lg bg-gray-900 text-white font-semibold hover:bg-black transition-colors disabled:opacity-50"><span x-text="account.loading ? 'Saving...' : 'Save Changes'"></span></button>
                </div>
            </form>
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
