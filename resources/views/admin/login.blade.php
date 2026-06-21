@extends('layouts.bare')

@section('title', 'Login Portal — '.config('business.name'))

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm card-dark p-10" x-data="adminLogin()">
        <h1 class="font-black text-3xl mb-2 text-white">Login Portal</h1>
        <p class="text-gray-400 mb-8 text-sm">Sign in with your username and password.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="login-username" class="block text-sm text-gray-300 mb-1.5">Username</label>
                <input id="login-username" type="text" x-model="username" class="input-dark" required autocomplete="username" autofocus>
            </div>
            <div>
                <label for="login-password" class="block text-sm text-gray-300 mb-1.5">Password</label>
                <input id="login-password" type="password" x-model="password" class="input-dark" required autocomplete="current-password">
            </div>

            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" x-model="remember" class="w-4 h-4 rounded border-charcoal-600 accent-brand-yellow cursor-pointer">
                <span class="text-sm text-gray-400">Remember username</span>
            </label>

            <p x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></p>

            <button type="submit" :disabled="loading" class="btn-primary w-full py-3 mt-1">
                <span x-text="loading ? 'Signing in...' : 'Sign In'"></span>
            </button>
        </form>
    </div>
</div>

<script>
function adminLogin() {
    return {
        username: '',
        password: '',
        remember: false,
        error: '',
        loading: false,
        init() {
            const saved = localStorage.getItem('admin_remembered_username') ?? '';
            this.username = saved;
            this.remember = !!saved;
        },
        async submit() {
            this.error = '';
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.login.post') }}', {
                    method: 'POST',
                    headers: window.jsonHeaders(true),
                    body: JSON.stringify({ username: this.username, password: this.password }),
                });
                if (res.ok) {
                    if (this.remember) localStorage.setItem('admin_remembered_username', this.username);
                    else localStorage.removeItem('admin_remembered_username');
                    const data = await res.json();
                    const home = data.role === 'employee' ? '{{ route('admin.my-schedule') }}' : '{{ route('admin.dashboard') }}';
                    window.location.href = data.mustChangePassword ? '{{ route('admin.change-password') }}' : home;
                } else {
                    const data = await res.json().catch(() => ({}));
                    this.error = data.error || 'Login failed.';
                }
            } catch {
                this.error = 'Something went wrong. Try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
