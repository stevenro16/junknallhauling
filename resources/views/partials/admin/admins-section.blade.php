<div x-data="adminsManager({
        admins: @js($admins),
        urls: {
            index: '{{ route('admin.admins.index') }}',
            store: '{{ route('admin.admins.store') }}',
            update: '{{ route('admin.admins.update', '__ID__') }}',
            destroy: '{{ route('admin.admins.destroy', '__ID__') }}',
        },
    })" class="space-y-6">

    {{-- Create admin --}}
    <div class="card-dark p-5">
        <div class="text-sm font-semibold text-gray-200 mb-3">Create Admin</div>
        <div id="service-catalog-form" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <div><label class="block text-xs text-gray-400 mb-1">Username</label><input type="text" x-model="nw.username" class="input-dark" placeholder="username"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Temporary Password</label><input type="text" x-model="nw.password" class="input-dark" placeholder="min 6 chars"></div>
            <div><button @click="create()" class="btn-primary text-sm py-2 px-4 w-full"><x-icon name="plus" class="w-4 h-4"/> Create</button></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-2">New admins must change their password on first login.</p>
        <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
    </div>

    {{-- List --}}
    <div class="card-dark p-5">
        <table class="w-full text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Username</th><th class="text-left py-2">Created</th><th class="text-left py-2">Must Change</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="a in admins" :key="a.id">
                    <tr class="border-b border-charcoal-700/60">
                        <td class="py-2 font-medium" x-text="a.username"></td>
                        <td class="py-2 text-gray-400" x-text="date(a.created_at)"></td>
                        <td class="py-2"><span x-text="a.must_change_password ? 'Yes' : 'No'" :class="a.must_change_password ? 'text-amber-400' : 'text-gray-500'"></span></td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <button @click="resetPassword(a)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700">Reset Password</button>
                            <button @click="remove(a)" class="text-xs px-2 py-1 rounded border border-red-600/60 text-red-400 hover:bg-red-900/30">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
