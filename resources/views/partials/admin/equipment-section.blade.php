<div x-data="equipmentCatalog({
        equipment: @js($equipment),
        urls: {
            index: '{{ route('admin.api.equipment.index') }}',
            store: '{{ route('admin.api.equipment.store') }}',
            update: '{{ route('admin.api.equipment.update', '__ID__') }}',
            destroy: '{{ route('admin.api.equipment.destroy', '__ID__') }}',
        },
    })" class="space-y-6">

    {{-- Add form --}}
    <div class="card-dark p-5">
        <div class="text-sm font-semibold text-gray-200 mb-3">Add Equipment</div>
        <div id="equipment-catalog-form" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2"><label class="block text-xs text-gray-400 mb-1">Name</label><input type="text" x-model="nw.name" class="input-dark" placeholder="e.g. Scissor Lift (19-26 ft)"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Avg $/hr</label><input type="number" x-model="nw.cost" class="input-dark" placeholder="(optional)"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Daily Rate</label><input type="number" x-model="nw.daily" class="input-dark" placeholder="(optional)"></div>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <button @click="add()" class="btn-primary text-sm py-2 px-4"><x-icon name="plus" class="w-4 h-4"/> Add Equipment</button>
            <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-dark p-5">
        <table class="w-full text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Name</th><th class="text-left py-2">$/hr</th><th class="text-left py-2">Daily</th><th class="text-left py-2">Active</th><th class="text-left py-2">Customer</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="e in equipment" :key="e.id">
                    <tr class="border-b border-charcoal-700/60" :class="!e.active && 'opacity-50'">
                        <td class="py-2">
                            <span x-show="editingId !== e.id" x-text="e.name"></span>
                            <input x-show="editingId === e.id" x-model="ed.name" class="input-dark py-1 text-sm" x-cloak>
                        </td>
                        <td class="py-2">
                            <span x-show="editingId !== e.id">$<span x-text="money(e.avg_cost_per_hour)"></span></span>
                            <input x-show="editingId === e.id" type="number" x-model="ed.cost" class="input-dark py-1 text-sm w-24" x-cloak>
                        </td>
                        <td class="py-2">
                            <span x-show="editingId !== e.id">$<span x-text="money(e.daily_rate)"></span></span>
                            <input x-show="editingId === e.id" type="number" x-model="ed.daily" class="input-dark py-1 text-sm w-24" x-cloak>
                        </td>
                        <td class="py-2"><span x-text="e.active ? 'Yes' : 'No'" :class="e.active ? 'text-emerald-400' : 'text-gray-500'"></span></td>
                        <td class="py-2">
                            <button @click="toggleCustomerVisible(e)" type="button"
                                    class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                                    :class="e.customer_visible ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10' : 'border-charcoal-600 text-gray-500 hover:bg-charcoal-700'"
                                    :title="e.customer_visible ? 'Shown on the public quote form — click to hide' : 'Hidden from the public quote form — click to show'"
                                    x-text="e.customer_visible ? 'Visible' : 'Hidden'"></button>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <template x-if="editingId === e.id">
                                <span>
                                    <button @click="saveEdit(e)" class="text-xs px-2 py-1 rounded bg-emerald-600 text-white">Save</button>
                                    <button @click="cancelEdit()" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300">Cancel</button>
                                </span>
                            </template>
                            <template x-if="editingId !== e.id">
                                <span>
                                    <button @click="startEdit(e)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700">Edit</button>
                                    <button @click="toggleActive(e)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700" x-text="e.active ? 'Hide' : 'Show'"></button>
                                    <button @click="remove(e)" class="text-xs px-2 py-1 rounded border border-red-500/40 text-red-400 hover:bg-red-500/10">Delete</button>
                                </span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
