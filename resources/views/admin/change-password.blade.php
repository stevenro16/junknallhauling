@extends('layouts.bare')

@section('title', 'Change Password — '.config('business.name'))

@php($isEmployee = session('admin_role') === 'employee')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md card-dark p-10" x-data="changePassword()">
        <h1 class="font-black text-3xl mb-2 text-white">Change Password</h1>
        <p class="text-gray-400 mb-8 text-sm">You must change your password before continuing.@if($isEmployee) Please also add an email we can use for password resets.@endif</p>

        <form @submit.prevent="submit" class="space-y-4">
            @if($isEmployee)
            <div>
                <label for="cp-email" class="block text-sm text-gray-300 mb-1.5">Email Address</label>
                <input id="cp-email" type="email" x-model="email" class="input-dark" required autocomplete="email" placeholder="you@example.com">
            </div>
            @endif
            <div>
                <label for="cp-new" class="block text-sm text-gray-300 mb-1.5">New Password</label>
                <input id="cp-new" type="password" x-model="newPassword" class="input-dark" required minlength="6" autocomplete="new-password" @if(!$isEmployee) autofocus @endif>
            </div>
            <div>
                <label for="cp-confirm" class="block text-sm text-gray-300 mb-1.5">Confirm New Password</label>
                <input id="cp-confirm" type="password" x-model="confirmPassword" class="input-dark" required minlength="6" autocomplete="new-password">
            </div>

            <p x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></p>
            <p x-show="success" class="text-green-400 text-sm" x-cloak>&check; Password changed. Redirecting...</p>

            <button type="submit" :disabled="loading" class="btn-primary w-full py-3 mt-1">
                <span x-text="loading ? 'Updating...' : 'Change Password'"></span>
            </button>
        </form>
    </div>
</div>

<script>
function changePassword() {
    return {
        newPassword: '',
        confirmPassword: '',
        email: '',
        isEmployee: {{ $isEmployee ? 'true' : 'false' }},
        error: '',
        success: false,
        loading: false,
        async submit() {
            this.error = '';
            if (this.newPassword.length < 6) { this.error = 'New password must be at least 6 characters.'; return; }
            if (this.newPassword !== this.confirmPassword) { this.error = 'New passwords do not match.'; return; }
            if (this.isEmployee && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.email)) { this.error = 'Please enter a valid email address.'; return; }
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.change-password.update') }}', {
                    method: 'POST',
                    headers: window.jsonHeaders(true),
                    body: JSON.stringify({ newPassword: this.newPassword, email: this.email }),
                });
                if (res.ok) {
                    this.success = true;
                    setTimeout(() => window.location.href = '{{ route($isEmployee ? 'admin.my-schedule' : 'admin.dashboard') }}', 900);
                } else {
                    const data = await res.json().catch(() => ({}));
                    this.error = data.error || 'Failed to change password.';
                }
            } catch {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
