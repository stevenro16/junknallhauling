<div x-data="equipmentCatalog({
        equipment: @js($equipment),
        agreements: @js($agreements),
        urls: {
            index: '{{ route('admin.api.equipment.index') }}',
            store: '{{ route('admin.api.equipment.store') }}',
            update: '{{ route('admin.api.equipment.update', '__ID__') }}',
            destroy: '{{ route('admin.api.equipment.destroy', '__ID__') }}',
        },
    })" class="space-y-4">

    {{-- Add button --}}
    <div class="flex justify-end">
        <button @click="openCreate()" class="btn-primary text-sm py-2.5 px-4 inline-flex items-center justify-center gap-1"><x-icon name="plus" class="w-4 h-4"/> Add Equipment</button>
    </div>

    {{-- List: table on desktop, cards on mobile (read-only; Edit/Add open the modal) --}}
    <div class="card-dark p-5">
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm text-gray-200">
            <thead class="text-xs uppercase tracking-wider text-gray-400 border-b border-charcoal-600">
                <tr><th class="text-left py-2">Name</th><th class="text-left py-2">Pricing</th><th class="text-left py-2">Active</th><th class="text-left py-2">Customer</th><th class="text-left py-2">Agreement</th><th class="text-left py-2">Instructions</th><th class="text-right py-2">Actions</th></tr>
            </thead>
            <tbody>
                <template x-for="e in equipment" :key="e.id">
                    <tr class="border-b border-charcoal-700/60" :class="!e.active && 'opacity-50'">
                        <td class="py-2.5 pr-3 font-medium text-gray-100" x-text="e.name"></td>
                        <td class="py-2.5 pr-3 whitespace-nowrap" x-text="pricingLabel(e)"></td>
                        <td class="py-2.5"><span x-text="e.active ? 'Yes' : 'No'" :class="e.active ? 'text-emerald-400' : 'text-gray-500'"></span></td>
                        <td class="py-2.5">
                            <button @click="toggleCustomerVisible(e)" type="button"
                                    class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                                    :class="e.customer_visible ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10' : 'border-charcoal-600 text-gray-500 hover:bg-charcoal-700'"
                                    :title="e.customer_visible ? 'Shown on the public quote form — click to hide' : 'Hidden from the public quote form — click to show'"
                                    x-text="e.customer_visible ? 'Visible' : 'Hidden'"></button>
                        </td>
                        <td class="py-2.5 pr-3 text-xs text-amber-300/90" x-text="agreementName(e) || '—'"></td>
                        <td class="py-2.5 pr-3"><span class="text-xs text-gray-400 block max-w-[220px] truncate" :title="e.customer_instructions || ''" x-text="e.customer_instructions || '—'"></span></td>
                        <td class="py-2.5 text-right whitespace-nowrap">
                            <button @click="startEdit(e)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700">Edit</button>
                            <button @click="toggleActive(e)" class="text-xs px-2 py-1 rounded border border-charcoal-600 text-gray-300 hover:bg-charcoal-700" x-text="e.active ? 'Hide' : 'Show'"></button>
                            <button @click="remove(e)" class="text-xs px-2 py-1 rounded border border-red-500/40 text-red-400 hover:bg-red-500/10">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="equipment.length === 0" class="text-sm text-gray-500 text-center py-6">No equipment yet — use “Add Equipment” above.</div>
      </div>

      {{-- Mobile cards --}}
      <div class="md:hidden space-y-3">
        <template x-for="e in equipment" :key="e.id">
            <div class="rounded-lg border border-charcoal-700 p-3" :class="!e.active && 'opacity-60'">
                <div class="flex items-start justify-between gap-2">
                    <div class="font-semibold text-gray-100 break-words" x-text="e.name"></div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full shrink-0" :class="e.active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-charcoal-700 text-gray-400'" x-text="e.active ? 'Active' : 'Hidden'"></span>
                </div>
                <div class="mt-1 text-sm text-gray-300" x-text="pricingLabel(e)"></div>
                <div class="mt-2">
                    <button @click="toggleCustomerVisible(e)" type="button"
                            class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                            :class="e.customer_visible ? 'border-emerald-500/40 text-emerald-400' : 'border-charcoal-600 text-gray-400'"
                            x-text="e.customer_visible ? '✓ Visible to customers' : 'Hidden from customers'"></button>
                </div>
                <div x-show="agreementName(e)" x-cloak class="mt-1.5 text-xs text-amber-300/90">Agreement: <span x-text="agreementName(e)"></span></div>
                <div x-show="e.customer_instructions" x-cloak class="mt-2 text-xs text-gray-400 whitespace-pre-wrap" x-text="e.customer_instructions"></div>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <button @click="startEdit(e)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700">Edit</button>
                    <button @click="toggleActive(e)" class="min-h-[42px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700" x-text="e.active ? 'Hide' : 'Show'"></button>
                    <button @click="remove(e)" class="min-h-[42px] rounded-lg border border-red-500/40 text-red-400 text-sm hover:bg-red-500/10">Delete</button>
                </div>
            </div>
        </template>
        <div x-show="equipment.length === 0" class="text-sm text-gray-500 text-center py-6">No equipment yet — use “Add Equipment” above.</div>
      </div>
    </div>

    {{-- Add / Edit modal (shared) --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-[100] flex items-start sm:items-center justify-center p-4 overflow-y-auto" @keydown.escape.window="closeForm()">
        <div class="absolute inset-0 bg-black/60" @click="closeForm()"></div>
        <div class="relative bg-charcoal-800 border border-charcoal-700 rounded-2xl shadow-2xl w-full max-w-2xl my-8">
            <div class="flex items-center justify-between px-5 py-4 border-b border-charcoal-700">
                <div class="text-sm font-semibold text-gray-200" x-text="editingId ? 'Edit Equipment' : 'Add Equipment'"></div>
                <button type="button" @click="closeForm()" class="p-1.5 -mr-1.5 text-gray-400 hover:text-white"><x-icon name="x" class="w-5 h-5"/></button>
            </div>
            <div class="p-5 space-y-4">
                <div><label class="block text-xs text-gray-400 mb-1">Name</label><input type="text" x-model="f.name" class="input-dark" placeholder="e.g. Oversized 10-Yard Dump Trailer"></div>

                @include('partials.admin.equipment-pricing-fields', ['s' => 'f'])

                <div><label class="block text-xs text-gray-400 mb-1">Customer Instructions <span class="text-gray-500">(optional)</span></label><textarea x-model="f.instructions" rows="2" class="input-dark" placeholder="Instructions shown to the customer"></textarea></div>
                <div><label class="block text-xs text-gray-400 mb-1">Agreement <span class="text-gray-500">(signed before the job is finalized)</span></label>
                    <select x-model="f.agreement_id" class="input-dark">
                        <option value="">— None —</option>
                        <template x-for="a in agreements" :key="a.id"><option :value="a.id" x-text="a.title"></option></template>
                    </select>
                </div>
                <span x-show="error" x-text="error" class="block text-red-400 text-sm" x-cloak></span>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-charcoal-700">
                <button type="button" @click="closeForm()" class="px-4 py-2 rounded-lg border border-charcoal-600 text-gray-300 text-sm hover:bg-charcoal-700">Cancel</button>
                <button type="button" @click="save()" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700" x-text="editingId ? 'Save changes' : 'Add equipment'"></button>
            </div>
        </div>
    </div>
</div>
