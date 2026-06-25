{{-- Customer photo lightbox (Final Review thumbnails) — click outside / Esc to close --}}
<div x-show="lightboxPhoto" x-cloak @keydown.escape.window="lightboxPhoto = ''" @click="lightboxPhoto = ''"
     class="fixed inset-0 bg-black/85 flex items-center justify-center z-[120] p-4">
    <button type="button" @click="lightboxPhoto = ''" class="absolute top-4 right-4 text-gray-300 hover:text-white p-2" aria-label="Close"><x-icon name="x" class="w-7 h-7"/></button>
    <img :src="lightboxPhoto" alt="Customer photo" class="max-w-full max-h-[90vh] rounded-2xl border border-gray-300 shadow-2xl object-contain bg-gray-900" @click.stop>
</div>

{{-- Photo full-size --}}
<div x-show="showPhotoModal" x-cloak class="fixed inset-0 bg-black/80 flex items-center justify-center z-[110] p-4" @click="showPhotoModal = false">
    <div class="max-w-[95vw] max-h-[90vh] overflow-auto" @click.stop>
        <div class="flex justify-end mb-2"><button type="button" @click="showPhotoModal = false" class="text-gray-300 hover:text-white text-sm px-3 py-1">Close &times;</button></div>
        <img :src="inquiry.photo_url || ''" alt="Customer photo" class="max-w-full max-h-[85vh] rounded-2xl border border-gray-300 shadow-2xl object-contain bg-gray-900">
        <p class="text-center text-xs text-gray-400 mt-2">Click outside or press close to dismiss</p>
    </div>
</div>

{{-- Left voicemail note --}}
<div x-show="showVoicemailModal" x-cloak class="fixed inset-0 bg-black/70 flex items-center justify-center z-[100] p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6">
            <h3 class="text-xl font-bold mb-3">Left Voicemail</h3>
            <p class="text-gray-700 mb-4 text-sm">Record details about the voicemail you left for the customer. This note will be visible to them on the status page.</p>
            <textarea x-model="voicemailNote" placeholder="E.g. Left message about rescheduling to Tuesday..." class="input-light w-full min-h-[120px] text-sm resize-y" rows="4"></textarea>
            <div class="flex flex-col gap-3 mt-5">
                <button type="button" @click="handleSaveVoicemail()" :disabled="saving" class="btn-primary w-full"><span x-text="saving ? 'Saving...' : 'Save Note & Mark as Left Voicemail'"></span></button>
                <button type="button" @click="showVoicemailModal = false; voicemailNote = ''" class="btn-outline w-full">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel confirmation --}}
<div x-show="showCancelConfirm" x-cloak class="fixed inset-0 bg-black/70 flex items-center justify-center z-[100] p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6">
            <h3 class="text-xl font-bold mb-3 text-red-600">Cancel this quote?</h3>
            <p class="text-gray-700 mb-6 text-sm">This will mark the quote as <strong>Cancelled</strong>. The customer will be able to see this status on their status page. This action can be reversed later by changing the status again.</p>
            <div class="flex flex-col gap-3">
                <button type="button" @click="handleConfirmCancel()" :disabled="saving" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-sm transition-colors disabled:opacity-50"><span x-text="saving ? 'Cancelling...' : 'Yes, Cancel This Quote'"></span></button>
                <button type="button" @click="showCancelConfirm = false" class="btn-outline w-full">No, Keep It Active</button>
            </div>
        </div>
    </div>
</div>
