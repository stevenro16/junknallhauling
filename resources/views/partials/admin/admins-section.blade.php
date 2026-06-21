<div x-data="adminsManager({
        admins: @js($admins),
        urls: {
            index: '{{ route('admin.admins.index') }}',
            store: '{{ route('admin.admins.store') }}',
            update: '{{ route('admin.admins.update', '__ID__') }}',
            destroy: '{{ route('admin.admins.destroy', '__ID__') }}',
        },
    })" class="space-y-6">

    {{-- Create account --}}
    <div class="card-dark p-5">
        <div class="text-sm font-semibold text-gray-200 mb-3">Create Account</div>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Role</label>
                <select x-model="nw.role" @change="setRole(nw.role)" class="input-dark">
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
            <div><label class="block text-xs text-gray-400 mb-1">Username</label><input type="text" x-model="nw.username" class="input-dark" placeholder="username"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Temporary Password</label><input type="text" x-model="nw.password" class="input-dark" placeholder="min 6 chars"></div>
            <div><button @click="create()" class="btn-primary text-sm py-2 px-4 w-full"><x-icon name="plus" class="w-4 h-4"/> Create</button></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-2">New accounts must change their password on first login. Employees default to <span class="font-mono text-gray-300">model123!</span> and record an email then.</p>
        <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
    </div>

    {{-- List: table on desktop, cards on mobile --}}
    <div class="card-dark p-5">
      <p class="text-xs text-gray-400 mb-3 flex items-center gap-1.5"><x-icon name="lock" class="w-3.5 h-3.5 text-amber-400 shrink-0"/> At least one admin account must always remain active — it can't be deactivated or deleted.</p>
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Username</th><th class="text-left py-2">Role</th><th class="text-left py-2">Email</th><th class="text-left py-2">Status</th><th class="text-left py-2">Created</th><th class="text-left py-2">Must Change</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="a in admins" :key="a.id">
                    <tr class="border-b border-charcoal-700/60" :class="!a.active && 'opacity-60'">
                        <td class="py-2 font-medium" x-text="a.username"></td>
                        <td class="py-2">
                            <span class="text-xs px-2 py-0.5 rounded-full border capitalize" :class="a.role === 'employee' ? 'border-sky-500/40 text-sky-300' : 'border-amber-500/40 text-amber-300'" x-text="a.role || 'admin'"></span>
                        </td>
                        <td class="py-2 text-gray-400" x-text="a.email || '—'"></td>
                        <td class="py-2">
                            <button x-show="!isLastActiveAdmin(a)" @click="toggleActive(a)" type="button"
                                    class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                                    :class="a.active ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10' : 'border-charcoal-600 text-gray-500 hover:bg-charcoal-700'"
                                    :title="a.active ? 'Active — click to deactivate' : 'Inactive — click to activate'"
                                    x-text="a.active ? 'Active' : 'Inactive'"></button>
                            <span x-show="isLastActiveAdmin(a)" x-cloak class="text-xs px-2 py-0.5 rounded-full border border-emerald-500/40 text-emerald-400" title="One admin account must always remain active">Active</span>
                        </td>
                        <td class="py-2 text-gray-400" x-text="date(a.created_at)"></td>
                        <td class="py-2"><span x-text="a.must_change_password ? 'Yes' : 'No'" :class="a.must_change_password ? 'text-amber-400' : 'text-gray-500'"></span></td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <button @click="resetPassword(a)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700">Reset Password</button>
                            <button @click="remove(a)" :disabled="isLastActiveAdmin(a)" :title="isLastActiveAdmin(a) ? 'One admin account must always remain active' : ''" class="text-xs px-2 py-1 rounded border border-red-600/60 text-red-400 hover:bg-red-900/30 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
      </div>

      {{-- Mobile cards --}}
      <div class="md:hidden space-y-3">
        <template x-for="a in admins" :key="a.id">
            <div class="rounded-lg border border-charcoal-700 p-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-100 break-words" x-text="a.username"></div>
                        <div class="text-xs text-gray-400 mt-0.5 break-words" x-text="a.email || 'No email on file'"></div>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-[10px] px-2 py-0.5 rounded-full border capitalize" :class="a.role === 'employee' ? 'border-sky-500/40 text-sky-300' : 'border-amber-500/40 text-amber-300'" x-text="a.role || 'admin'"></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full border" :class="a.active ? 'border-emerald-500/40 text-emerald-400' : 'border-charcoal-600 text-gray-500'" x-text="a.active ? 'Active' : 'Inactive'"></span>
                    </div>
                </div>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                    <span>Created <span x-text="date(a.created_at)"></span></span>
                    <span x-show="a.must_change_password" class="text-amber-400">Must change password</span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <button @click="resetPassword(a)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700">Reset</button>
                    <button x-show="!isLastActiveAdmin(a)" @click="toggleActive(a)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700" x-text="a.active ? 'Deactivate' : 'Activate'"></button>
                    <button x-show="isLastActiveAdmin(a)" disabled class="min-h-[42px] rounded-lg border border-charcoal-700 text-gray-600 text-sm cursor-not-allowed">Deactivate</button>
                    <button @click="remove(a)" :disabled="isLastActiveAdmin(a)" class="min-h-[42px] rounded-lg border border-red-600/60 text-red-400 text-sm hover:bg-red-900/30 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">Delete</button>
                </div>
                <p x-show="isLastActiveAdmin(a)" x-cloak class="mt-2 text-[11px] text-amber-400/90">One admin account must always remain active.</p>
            </div>
        </template>
        <div x-show="admins.length === 0" class="text-sm text-gray-500 text-center py-6">No accounts yet.</div>
      </div>
    </div>
</div>
