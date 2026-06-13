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
        <div id="service-catalog-form" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Key</label>
                <select x-model="nw.key" class="input-dark">
                    <option value="junk-removal">junk-removal</option>
                    <option value="10yd-dumpster">10yd-dumpster</option>
                    <option value="20yd-dumpster">20yd-dumpster</option>
                    <option value="equipment">equipment</option>
                    <option value="other">other</option>
                </select>
            </div>
            <div class="sm:col-span-2"><label class="block text-xs text-gray-400 mb-1">Label</label><input type="text" x-model="nw.label" class="input-dark" placeholder="Display label"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Default Price</label><input type="number" x-model="nw.price" class="input-dark" placeholder="(optional)"></div>
            <div><label class="block text-xs text-gray-400 mb-1">Duration (min)</label><input type="number" x-model="nw.duration" class="input-dark" placeholder="120"></div>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <button @click="add()" class="btn-primary text-sm py-2 px-4"><x-icon name="plus" class="w-4 h-4"/> Add Service</button>
            <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-dark p-5">
        <table class="w-full text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Key</th><th class="text-left py-2">Label</th><th class="text-left py-2">Price</th><th class="text-left py-2">Duration</th><th class="text-left py-2">Active</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="s in services" :key="s.id">
                    <tr class="border-b border-charcoal-700/60" :class="!s.active && 'opacity-50'">
                        <td class="py-2 font-mono text-xs" x-text="s.key"></td>
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
                                </span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
