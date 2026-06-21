<div x-data="servicesCatalog({
        services: @js($services),
        urls: {
            index: '{{ route('admin.api.services.index') }}',
            store: '{{ route('admin.api.services.store') }}',
            update: '{{ route('admin.api.services.update', '__ID__') }}',
            destroy: '{{ route('admin.api.services.destroy', '__ID__') }}',
        },
    })" class="space-y-6">

    {{-- Add form --}}
    <div class="card-dark p-5">
        <div class="text-sm font-semibold text-gray-200 mb-3">Add a Service</div>
        <div id="service-catalog-form" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2"><label class="block text-xs text-gray-400 mb-1">Service Name</label><input type="text" x-model="nw.label" class="input-dark" placeholder="e.g. Junk Removal"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Default Price</label><input type="number" x-model="nw.price" class="input-dark" placeholder="(optional)"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Duration (min)</label><input type="number" x-model="nw.duration" class="input-dark" placeholder="120"></div>
        </div>
        <div class="mt-3">
            <label class="block text-xs text-gray-400 mb-1">Customer Instructions <span class="text-gray-500">(optional)</span></label>
            <textarea x-model="nw.instructions" rows="2" class="input-dark" placeholder="Instructions for the customer (used in later workflows)"></textarea>
        </div>
        <label class="mt-3 flex items-center gap-2 w-fit text-sm text-gray-300 cursor-pointer select-none">
            <input type="checkbox" x-model="nw.customerVisible" class="w-4 h-4 accent-emerald-500">
            <span>Visible on the customer quote form</span>
        </label>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <button @click="add()" class="btn-primary text-sm py-2.5 px-4 w-full sm:w-auto inline-flex items-center justify-center gap-1"><x-icon name="plus" class="w-4 h-4"/> Add Service</button>
            <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
        </div>
    </div>

    {{-- List: table on desktop, cards on mobile --}}
    <div class="card-dark p-5">
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Service Name</th><th class="text-left py-2">Price</th><th class="text-left py-2">Duration</th><th class="text-left py-2">Active</th><th class="text-left py-2">Customer</th><th class="text-left py-2">Instructions</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="s in services" :key="s.id">
                    <tr class="border-b border-charcoal-700/60" :class="!s.active && 'opacity-50'">
                        <td class="py-2">
                            <span x-show="editingId !== s.id" x-text="s.label"></span>
                            <input x-show="editingId === s.id" x-model="ed.label" class="input-dark py-1 text-sm" x-cloak>
                        </td>
                        <td class="py-2">
                            <span x-show="editingId !== s.id">$<span x-text="money(s.default_price)"></span></span>
                            <input x-show="editingId === s.id" type="number" x-model="ed.price" class="input-dark py-1 text-sm w-24" x-cloak>
                        </td>
                        <td class="py-2">
                            <span x-show="editingId !== s.id"><span x-text="s.default_duration_minutes"></span>m</span>
                            <input x-show="editingId === s.id" type="number" x-model="ed.duration" class="input-dark py-1 text-sm w-20" x-cloak>
                        </td>
                        <td class="py-2"><span x-text="s.active ? 'Yes' : 'No'" :class="s.active ? 'text-emerald-400' : 'text-gray-500'"></span></td>
                        <td class="py-2">
                            <button @click="toggleCustomerVisible(s)" type="button"
                                    class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                                    :class="s.customer_visible ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10' : 'border-charcoal-600 text-gray-500 hover:bg-charcoal-700'"
                                    :title="s.customer_visible ? 'Shown on the public quote form — click to hide' : 'Hidden from the public quote form — click to show'"
                                    x-text="s.customer_visible ? 'Visible' : 'Hidden'"></button>
                        </td>
                        <td class="py-2">
                            <span x-show="editingId !== s.id" x-text="s.customer_instructions || '—'" class="text-xs text-gray-400 block max-w-[220px] truncate" :title="s.customer_instructions || ''"></span>
                            <textarea x-show="editingId === s.id" x-model="ed.instructions" rows="2" class="input-dark py-1 text-sm w-56" x-cloak placeholder="Customer instructions"></textarea>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <template x-if="editingId === s.id">
                                <span>
                                    <button @click="saveEdit(s)" class="text-xs px-2 py-1 rounded bg-emerald-600 text-white">Save</button>
                                    <button @click="cancelEdit()" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300">Cancel</button>
                                </span>
                            </template>
                            <template x-if="editingId !== s.id">
                                <span>
                                    <button @click="startEdit(s)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700">Edit</button>
                                    <button @click="toggleActive(s)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700" x-text="s.active ? 'Hide' : 'Show'"></button>
                                    <button @click="remove(s)" class="text-xs px-2 py-1 rounded border border-red-500/40 text-red-400 hover:bg-red-500/10">Delete</button>
                                </span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
      </div>

      {{-- Mobile cards --}}
      <div class="md:hidden space-y-3">
        <template x-for="s in services" :key="s.id">
            <div class="rounded-lg border border-charcoal-700 p-3" :class="!s.active && 'opacity-60'">
                {{-- View --}}
                <template x-if="editingId !== s.id">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-semibold text-gray-100 break-words" x-text="s.label"></div>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full shrink-0" :class="s.active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-charcoal-700 text-gray-400'" x-text="s.active ? 'Active' : 'Hidden'"></span>
                        </div>
                        <div class="mt-1 text-sm text-gray-300">$<span x-text="money(s.default_price)"></span> &middot; <span x-text="s.default_duration_minutes"></span> min</div>
                        <div class="mt-2">
                            <button @click="toggleCustomerVisible(s)" type="button"
                                    class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                                    :class="s.customer_visible ? 'border-emerald-500/40 text-emerald-400' : 'border-charcoal-600 text-gray-400'"
                                    x-text="s.customer_visible ? '✓ Visible to customers' : 'Hidden from customers'"></button>
                        </div>
                        <div x-show="s.customer_instructions" x-cloak class="mt-2 text-xs text-gray-400 whitespace-pre-wrap" x-text="s.customer_instructions"></div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <button @click="startEdit(s)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700">Edit</button>
                            <button @click="toggleActive(s)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700" x-text="s.active ? 'Hide' : 'Show'"></button>
                            <button @click="remove(s)" class="min-h-[42px] rounded-lg border border-red-500/40 text-red-400 text-sm hover:bg-red-500/10">Delete</button>
                        </div>
                    </div>
                </template>
                {{-- Edit --}}
                <template x-if="editingId === s.id">
                    <div class="space-y-2">
                        <div><label class="block text-xs text-gray-400 mb-1">Service Name</label><input x-model="ed.label" class="input-dark"></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="block text-xs text-gray-400 mb-1">Price</label><input type="number" x-model="ed.price" class="input-dark"></div>
                            <div><label class="block text-xs text-gray-400 mb-1">Duration (min)</label><input type="number" x-model="ed.duration" class="input-dark"></div>
                        </div>
                        <div><label class="block text-xs text-gray-400 mb-1">Customer Instructions</label><textarea x-model="ed.instructions" rows="2" class="input-dark"></textarea></div>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button @click="saveEdit(s)" class="min-h-[44px] rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Save</button>
                            <button @click="cancelEdit()" class="min-h-[44px] rounded-lg border border-charcoal-600 text-gray-300 text-sm hover:bg-charcoal-700">Cancel</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <div x-show="services.length === 0" class="text-sm text-gray-500 text-center py-6">No services yet.</div>
      </div>
    </div>
</div>
