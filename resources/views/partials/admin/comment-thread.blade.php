{{-- Threaded notes on a quote. Pass $postUrl (where to POST) and $comments (array).
     Internal by default; the author can flag a note customer-visible. --}}
<div x-data="commentThread({ postUrl: '{{ $postUrl }}', comments: @js($comments) })">
    <div class="text-sm font-semibold text-gray-800 mb-3">Notes &amp; Comments</div>

    {{-- Existing thread --}}
    <div class="space-y-2 mb-4">
        <template x-for="c in comments" :key="c.id">
            <div class="rounded-lg border p-3"
                 :class="c.customer_visible ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200 bg-gray-50'">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-700" x-text="c.author_name || 'Staff'"></span>
                    <span class="text-[10px] shrink-0 px-1.5 py-0.5 rounded-full font-medium"
                          :class="c.customer_visible ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'"
                          x-text="c.customer_visible ? 'Customer-visible' : 'Internal'"></span>
                </div>
                <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="c.body"></p>
                <div class="text-[10px] text-gray-400 mt-1" x-text="fmt(c.created_at)"></div>
            </div>
        </template>
        <div x-show="comments.length === 0" class="text-sm text-gray-400 py-3 text-center">No notes yet.</div>
    </div>

    {{-- Add a note --}}
    <div class="border-t border-gray-100 pt-3">
        <textarea x-model="body" rows="3" placeholder="Add a note…" class="input-light text-sm w-full py-2"></textarea>
        <div class="flex items-center justify-between gap-3 mt-2">
            <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none">
                <input type="checkbox" x-model="customerVisible" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                Make visible to customer
            </label>
            <button type="button" @click="submit()" :disabled="submitting" class="btn-primary py-2 px-5 text-sm">
                <span x-text="submitting ? 'Posting…' : 'Post note'"></span>
            </button>
        </div>
        <p x-show="error" x-cloak class="text-xs text-red-600 mt-1" x-text="error"></p>
        <p x-show="customerVisible" x-cloak class="text-[11px] text-emerald-600 mt-1">This note will show on the customer's status page.</p>
    </div>
</div>
