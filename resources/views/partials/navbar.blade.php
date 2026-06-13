@php
    $isAdmin   = session()->has('admin_id');
    $adminUser = session('admin_username');
@endphp

<div x-data="navbar({
    loginUrl: '{{ route('admin.login.post') }}',
    logoutUrl: '{{ route('admin.logout') }}',
    changePwdUrl: '{{ route('admin.change-password.update') }}',
    dashboardUrl: '{{ route('admin.dashboard') }}',
    changePwdPage: '{{ route('admin.change-password') }}',
})" @keydown.escape.window="showLogin = false; showPwd = false">

    <nav class="border-b border-white/10 bg-charcoal-800 sticky top-0 z-50">
        <div class="container-wide flex h-16 items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="/images/logo.jpg" alt="{{ config('business.name') }}" width="160" height="42"
                     class="h-9 w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
            </a>

            <div class="flex items-center gap-4 sm:gap-8 text-sm font-medium">
                <a href="{{ route('services') }}" class="text-slate-200 hover:text-[#F8C820] transition-colors hidden sm:block">Services</a>
                <a href="{{ route('about') }}" class="text-slate-200 hover:text-[#F8C820] transition-colors hidden sm:block">About</a>
                <a href="{{ route('reviews') }}" class="text-slate-200 hover:text-[#F8C820] transition-colors hidden sm:block">Reviews</a>
                <a href="{{ route('contact') }}" class="text-slate-200 hover:text-[#F8C820] transition-colors">Get Quote</a>
                <a href="{{ route('status') }}" class="text-slate-200 hover:text-[#F8C820] transition-colors font-semibold">Check Status</a>
                <a href="tel:{{ config('business.phoneRaw') }}" class="flex items-center gap-2 text-[#F8C820] hover:text-[#FACC15] font-semibold transition-colors">
                    <x-icon name="phone" class="w-4 h-4"/>
                    <span class="hidden sm:inline">{{ config('business.phone') }}</span>
                </a>

                <div class="border-l border-white/15 pl-3 sm:pl-5 sm:ml-2 flex items-center gap-4">
                    @if($isAdmin)
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-1.5 text-xs uppercase tracking-[1.5px] text-slate-400 hover:text-[#F8C820] transition-colors">
                            <x-icon name="lock" class="w-3 h-3"/> Admin Portal
                        </a>
                        <div class="relative" @click.outside="menuOpen = false">
                            <button type="button" @click="menuOpen = !menuOpen"
                                    class="flex items-center gap-1.5 text-xs uppercase tracking-[1.5px] text-red-400 hover:text-red-300 transition-colors">
                                Sign Out
                                <x-icon name="chevron-down" class="w-3 h-3 transition-transform" ::class="menuOpen && 'rotate-180'"/>
                            </button>
                            <div x-show="menuOpen" x-transition x-cloak
                                 class="absolute right-0 top-full mt-2 w-48 bg-charcoal-800 border border-charcoal-700 rounded-lg shadow-xl overflow-hidden z-50">
                                <button type="button" @click="openPwd()"
                                        class="w-full flex items-center gap-2.5 px-4 py-3 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors text-left">
                                    <x-icon name="key-round" class="w-4 h-4 shrink-0"/> Change Password
                                </button>
                                <div class="border-t border-charcoal-700"></div>
                                <button type="button" @click="signOut()"
                                        class="w-full flex items-center gap-2.5 px-4 py-3 text-sm text-red-400 hover:bg-charcoal-700 hover:text-red-300 transition-colors text-left">
                                    <x-icon name="log-out" class="w-4 h-4 shrink-0"/> Sign Out
                                </button>
                            </div>
                        </div>
                    @else
                        <button type="button" @click="openLogin()"
                                class="flex items-center gap-1.5 text-xs uppercase tracking-[1.5px] text-slate-400 hover:text-[#F8C820] transition-colors">
                            <x-icon name="lock" class="w-3 h-3"/> Admin Portal
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    {{-- Admin Login Modal --}}
    <div x-show="showLogin" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
         @click.self="showLogin = false">
        <div class="w-full max-w-sm bg-charcoal-800 border border-charcoal-700 rounded-xl p-6 relative shadow-2xl">
            <button type="button" @click="showLogin = false" class="absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <x-icon name="x" class="w-5 h-5"/>
            </button>
            <div class="flex items-center gap-2.5 mb-1">
                <x-icon name="lock" class="w-5 h-5 text-[#F8C820]"/>
                <h3 class="text-xl font-semibold text-gray-100">Admin Login</h3>
            </div>
            <p class="text-sm text-gray-400 mb-6">Sign in to the admin portal.</p>
            <form @submit.prevent="submitLogin()" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-1.5">Username</label>
                    <input type="text" x-model="login.username" class="input-dark" required autocomplete="username">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-1.5">Password</label>
                    <input type="password" x-model="login.password" class="input-dark" required autocomplete="current-password">
                </div>
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" x-model="login.remember" class="w-4 h-4 rounded border-charcoal-600 accent-brand-yellow cursor-pointer">
                    <span class="text-sm text-gray-400">Remember username</span>
                </label>
                <p x-show="login.error" x-text="login.error" class="text-red-400 text-sm" x-cloak></p>
                <button type="submit" :disabled="login.loading" class="btn-primary w-full py-3 mt-1">
                    <span x-text="login.loading ? 'Signing in...' : 'Sign In'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- Change Password Modal (self-service) --}}
    <div x-show="showPwd" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
         @click.self="showPwd = false">
        <div class="w-full max-w-md bg-charcoal-800 border border-charcoal-700 rounded-xl p-6 relative shadow-2xl">
            <button type="button" @click="showPwd = false" class="absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <x-icon name="x" class="w-5 h-5"/>
            </button>
            <h3 class="text-xl font-semibold text-gray-100 mb-1">Change Password</h3>
            <p class="text-sm text-gray-400 mb-6">Update your admin password. You will stay logged in.</p>
            <form @submit.prevent="submitPwd()" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-1.5">Current Password</label>
                    <input type="password" x-model="pwd.current" class="input-dark" required autocomplete="current-password">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-1.5">New Password</label>
                    <input type="password" x-model="pwd.next" class="input-dark" required minlength="6" autocomplete="new-password">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-1.5">Confirm New Password</label>
                    <input type="password" x-model="pwd.confirm" class="input-dark" required minlength="6" autocomplete="new-password">
                </div>
                <p x-show="pwd.error" x-text="pwd.error" class="text-red-400 text-sm" x-cloak></p>
                <p x-show="pwd.success" class="text-green-400 text-sm" x-cloak>&check; Password changed successfully. Closing...</p>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showPwd = false"
                            class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-charcoal-600 text-gray-300 hover:bg-charcoal-700 transition-colors">Cancel</button>
                    <button type="submit" :disabled="pwd.loading" class="btn-primary flex-1 py-2.5 text-sm">
                        <span x-text="pwd.loading ? 'Updating...' : 'Change Password'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
