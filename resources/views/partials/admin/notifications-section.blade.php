<div x-data="notificationSettings({
        events: @js(collect($notificationEvents)->map(fn ($e, $k) => array_merge(['key' => $k], $e))->values()),
        prefs: @js($notificationPrefs),
        customer: @js($customerNotify),
        updateUrl: '{{ route('admin.api.notifications.update') }}',
        customerUrl: '{{ route('admin.api.notifications.customer') }}',
        testSmsUrl: '{{ route('admin.api.notifications.test-sms') }}',
        testEmailUrl: '{{ route('admin.api.notifications.test-email') }}',
    })" class="space-y-8 max-w-2xl">

    <p class="text-xs text-amber-400/90 flex items-start gap-1.5">
        <x-icon name="alert" class="w-4 h-4 shrink-0 mt-0.5"/>
        <span>Texts send once Twilio is configured and emails once a mail provider is set up on the server. Use the test buttons below to confirm delivery.</span>
    </p>

    {{-- ============ Global: customer-facing notifications ============ --}}
    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-gray-800">Customer notifications</h3>
            <p class="text-sm text-gray-400 mt-1">Master switches for messages the site sends to customers. A customer is only contacted on the channel they picked as their preferred contact method — and only when that channel is on below. If their preferred channel is off, they aren’t contacted at all.</p>
        </div>

        <div class="card-dark p-5 space-y-3">
            <label class="flex items-center justify-between gap-4 rounded-lg border border-charcoal-700 p-3 cursor-pointer">
                <span>
                    <span class="text-sm text-gray-100 flex items-center gap-2"><x-icon name="mail" class="w-4 h-4"/> Email</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Email customers who prefer email.</span>
                </span>
                <input type="checkbox" x-model="customerEmail" class="w-5 h-5 accent-orange-500 cursor-pointer shrink-0">
            </label>
            <label class="flex items-center justify-between gap-4 rounded-lg border border-charcoal-700 p-3 cursor-pointer">
                <span>
                    <span class="text-sm text-gray-100 flex items-center gap-2"><x-icon name="phone" class="w-4 h-4"/> Text (SMS)</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Text customers who prefer phone contact.</span>
                </span>
                <input type="checkbox" x-model="customerSms" class="w-5 h-5 accent-orange-500 cursor-pointer shrink-0">
            </label>

            <div class="flex items-center gap-3 pt-1">
                <button @click="saveCustomer()" :disabled="customerSaving" class="btn-primary text-sm py-2.5 px-5 inline-flex items-center justify-center gap-1 disabled:opacity-60">
                    <span x-show="!customerSaving">Save</span>
                    <span x-show="customerSaving" x-cloak>Saving…</span>
                </button>
                <span x-show="customerSaved" x-cloak class="text-emerald-400 text-sm inline-flex items-center gap-1"><x-icon name="check" class="w-4 h-4"/> Saved</span>
                <span x-show="customerError" x-text="customerError" x-cloak class="text-red-400 text-sm"></span>
            </div>
        </div>
    </div>

    {{-- ============ Per-admin: your own notifications ============ --}}
    <div class="space-y-4 border-t border-charcoal-700 pt-8">
        <div>
            <h3 class="text-base font-semibold text-gray-800">Your notifications</h3>
            <p class="text-sm text-gray-400 mt-1">Choose which events you want to be notified about, and how. These are your personal settings — each admin has their own.</p>
        </div>

        {{-- Where to send --}}
        <div class="card-dark p-5 space-y-3">
            <div class="text-sm font-semibold text-gray-200">Where to send</div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Email address</label>
                <input type="email" x-model="email" class="input-dark" placeholder="you@example.com">
                <div class="flex items-center gap-3 mt-2">
                    <button type="button" @click="sendTestEmail()" :disabled="emailTesting" class="btn-outline text-xs py-1.5 px-3 inline-flex items-center gap-1 disabled:opacity-60">
                        <x-icon name="send" class="w-3.5 h-3.5"/>
                        <span x-show="!emailTesting">Send test email</span>
                        <span x-show="emailTesting" x-cloak>Sending…</span>
                    </button>
                    <span x-show="emailTestResult" x-text="emailTestResult" x-cloak :class="emailTestOk ? 'text-emerald-400' : 'text-red-400'" class="text-xs"></span>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Mobile number <span class="text-gray-500">(for text alerts)</span></label>
                <input type="tel" x-model="phone" class="input-dark" placeholder="(909) 555-1234">
                <div class="flex items-center gap-3 mt-2">
                    <button type="button" @click="sendTest()" :disabled="testing" class="btn-outline text-xs py-1.5 px-3 inline-flex items-center gap-1 disabled:opacity-60">
                        <x-icon name="send" class="w-3.5 h-3.5"/>
                        <span x-show="!testing">Send test text</span>
                        <span x-show="testing" x-cloak>Sending…</span>
                    </button>
                    <span x-show="testResult" x-text="testResult" x-cloak :class="testOk ? 'text-emerald-400' : 'text-red-400'" class="text-xs"></span>
                </div>
            </div>
        </div>

        {{-- Per-event channel choices --}}
        <div class="card-dark p-5">
            <div class="grid grid-cols-[1fr_auto_auto] gap-x-4 gap-y-1 items-center">
                <div class="text-sm font-semibold text-gray-200">Event</div>
                <div class="text-xs font-semibold text-gray-300 w-14 text-center">Email</div>
                <div class="text-xs font-semibold text-gray-300 w-14 text-center">Text</div>

                <template x-for="ev in events" :key="ev.key">
                    <template x-if="true">
                        <div class="contents">
                            <div class="py-3 border-t border-charcoal-700">
                                <div class="text-sm text-gray-100" x-text="ev.label"></div>
                                <div class="text-xs text-gray-500" x-text="ev.description"></div>
                            </div>
                            <div class="py-3 border-t border-charcoal-700 flex justify-center">
                                <input type="checkbox" x-model="channels[ev.key].email" class="w-5 h-5 accent-orange-500 cursor-pointer">
                            </div>
                            <div class="py-3 border-t border-charcoal-700 flex justify-center">
                                <input type="checkbox" x-model="channels[ev.key].sms" class="w-5 h-5 accent-orange-500 cursor-pointer">
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button @click="save()" :disabled="saving" class="btn-primary text-sm py-2.5 px-5 inline-flex items-center justify-center gap-1 disabled:opacity-60">
                <span x-show="!saving">Save settings</span>
                <span x-show="saving" x-cloak>Saving…</span>
            </button>
            <span x-show="saved" x-cloak class="text-emerald-400 text-sm inline-flex items-center gap-1"><x-icon name="check" class="w-4 h-4"/> Saved</span>
            <span x-show="error" x-text="error" x-cloak class="text-red-400 text-sm"></span>
        </div>
    </div>
</div>
