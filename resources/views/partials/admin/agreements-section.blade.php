<div x-data="agreementCatalog({
        agreements: @js($agreements),
        urls: {
            index: '{{ route('admin.api.agreements.index') }}',
            store: '{{ route('admin.api.agreements.store') }}',
            update: '{{ route('admin.api.agreements.update', '__ID__') }}',
            destroy: '{{ route('admin.api.agreements.destroy', '__ID__') }}',
        },
    })" class="space-y-6">

    <p class="text-sm text-gray-400">Editable agreements the customer reviews and signs. Attach one to a service or equipment item (in the Service / Equipment Catalog) and it becomes required before that job is finalized. A snapshot of the exact terms is frozen onto each signed copy.</p>

    {{-- Add form --}}
    <div class="card-dark p-5">
        <div class="text-sm font-semibold text-gray-200 mb-3">Add Agreement</div>
        <div class="space-y-3">
            <div><label class="block text-xs text-gray-400 mb-1">Title</label><input type="text" x-model="nw.title" class="input-dark" placeholder="e.g. Dumpster Rental Agreement"></div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Acknowledgment items <span class="text-gray-500">(one per line — each becomes a required checkbox)</span></label>
                <textarea x-model="nw.acknowledgments" rows="5" class="input-dark" placeholder="I understand any overage or additional days will be billed.&#10;Full payment is due on the date this agreement is signed."></textarea>
            </div>
            <div><label class="block text-xs text-gray-400 mb-1">Additional instructions <span class="text-gray-500">(optional — prohibited items, pricing notes, etc.)</span></label><textarea x-model="nw.instructions" rows="3" class="input-dark" placeholder="Shown as a block beneath the checkboxes."></textarea></div>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <button @click="add()" class="btn-primary text-sm py-2.5 px-4 w-full sm:w-auto inline-flex items-center justify-center gap-1"><x-icon name="plus" class="w-4 h-4"/> Add Agreement</button>
            <span x-show="error" x-text="error" class="text-red-400 text-sm" x-cloak></span>
        </div>
    </div>

    {{-- List --}}
    <div class="card-dark p-5 space-y-3">
        <template x-for="a in agreements" :key="a.id">
            <div class="rounded-lg border border-charcoal-700 p-4" :class="!a.active && 'opacity-60'">
                {{-- View --}}
                <template x-if="editingId !== a.id">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-semibold text-gray-100 break-words" x-text="a.title"></div>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full shrink-0" :class="a.active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-charcoal-700 text-gray-400'" x-text="a.active ? 'Active' : 'Hidden'"></span>
                        </div>
                        <div class="mt-1 text-xs text-gray-400"><span x-text="ackCount(a)"></span> acknowledgment item<span x-show="ackCount(a) !== 1">s</span></div>
                        <ul class="mt-2 space-y-1 text-sm text-gray-300 list-disc pl-5">
                            <template x-for="(ack, i) in a.acknowledgments" :key="i"><li x-text="ack"></li></template>
                        </ul>
                        <div x-show="a.instructions" x-cloak class="mt-2 text-xs text-gray-400 whitespace-pre-wrap" x-text="a.instructions"></div>
                        <div class="mt-3 grid grid-cols-3 gap-2 max-w-md">
                            <button @click="startEdit(a)" class="min-h-[40px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700">Edit</button>
                            <button @click="toggleActive(a)" class="min-h-[40px] rounded-lg border border-charcoal-600 text-gray-200 text-sm hover:bg-charcoal-700" x-text="a.active ? 'Hide' : 'Show'"></button>
                            <button @click="remove(a)" class="min-h-[40px] rounded-lg border border-red-500/40 text-red-400 text-sm hover:bg-red-500/10">Delete</button>
                        </div>
                    </div>
                </template>
                {{-- Edit --}}
                <template x-if="editingId === a.id">
                    <div class="space-y-2">
                        <div><label class="block text-xs text-gray-400 mb-1">Title</label><input x-model="ed.title" class="input-dark"></div>
                        <div><label class="block text-xs text-gray-400 mb-1">Acknowledgment items (one per line)</label><textarea x-model="ed.acknowledgments" rows="6" class="input-dark"></textarea></div>
                        <div><label class="block text-xs text-gray-400 mb-1">Additional instructions</label><textarea x-model="ed.instructions" rows="3" class="input-dark"></textarea></div>
                        <div class="grid grid-cols-2 gap-2 pt-1 max-w-sm">
                            <button @click="saveEdit(a)" class="min-h-[44px] rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Save</button>
                            <button @click="cancelEdit()" class="min-h-[44px] rounded-lg border border-charcoal-600 text-gray-300 text-sm hover:bg-charcoal-700">Cancel</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <div x-show="agreements.length === 0" class="text-sm text-gray-500 text-center py-6">No agreements yet. Add one above, then attach it to a service or equipment item.</div>
    </div>
</div>
