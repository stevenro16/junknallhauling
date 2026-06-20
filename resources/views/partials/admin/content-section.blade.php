@php
    // Group the editable fields by their 'group' for display, preserving keys.
    $grouped = [];
    foreach (\App\Models\SiteContent::fields() as $key => $field) {
        $grouped[$field['group']][$key] = $field;
    }

    // Icon choices for the service cards.
    $iconChoices = ['truck', 'package', 'hammer', 'wrench', 'settings', 'dollar-sign',
        'map-pin', 'calendar', 'check-circle', 'star', 'image', 'send', 'clock', 'file-text'];

    // Initial card sets (one per 'cards' field), with stable uids for Alpine + Trix.
    $initialCardSets = [];
    $cardMax = [];
    foreach (\App\Models\SiteContent::fields() as $cardKey => $cardField) {
        if (($cardField['type'] ?? '') !== 'cards') {
            continue;
        }
        $initialCardSets[$cardKey] = collect(\App\Models\SiteContent::cards($cardKey))
            ->map(fn ($c, $i) => array_merge(['icon' => 'truck', 'image' => '', 'title' => '', 'subheader' => '', 'body' => ''], $c, ['uid' => $cardKey.$i]))
            ->values();
        $cardMax[$cardKey] = $cardField['max'] ?? 4;
    }
@endphp

<div x-data="siteContent({
        updateUrl: '{{ route('admin.api.content.update') }}',
        cardSets: @js($initialCardSets),
        cardMax: @js($cardMax),
        icons: @js($iconChoices),
    })"
    @input="ready && (dirty = true)" @change="ready && (dirty = true)" @trix-change="ready && (dirty = true)"
    class="max-w-5xl space-y-6 pb-4">

    <p class="text-sm text-gray-500">
        Edit the marketing copy and serving areas shown on the public site. Changes go live as soon as you save.
    </p>

    @foreach($grouped as $groupName => $fields)
        <div class="card-light p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">{{ $groupName }}</h3>
            <div class="space-y-6">
                @foreach($fields as $key => $field)

                    @if($field['type'] === 'cards')
                        {{-- Service card manager with live preview (one set per cards field) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>
                                <button type="button" @click="addCard('{{ $key }}')" x-show="cardSets['{{ $key }}'].length < maxFor('{{ $key }}')"
                                        class="text-xs font-semibold px-2.5 py-1 rounded bg-charcoal-800 text-[#F8C820] hover:bg-black">
                                    + Add card
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">Up to {{ $field['max'] ?? 4 }} cards.</p>

                            <div class="space-y-4">
                                <template x-for="(card, i) in cardSets['{{ $key }}']" :key="card.uid">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="grid lg:grid-cols-2 gap-5">

                                            {{-- Editor --}}
                                            <div>
                                                <div class="flex items-center mb-3">
                                                    <span class="text-xs font-semibold text-gray-500" x-text="'Card ' + (i + 1)"></span>
                                                    <button type="button" @click="removeCard('{{ $key }}', i)"
                                                            class="ml-auto text-xs text-red-500 hover:text-red-600">Remove</button>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Icon</label>
                                                        <select x-model="card.icon" :disabled="!!card.image"
                                                                class="input-light text-sm" :class="card.image && 'opacity-40'">
                                                            <template x-for="ic in icons" :key="ic">
                                                                <option :value="ic" x-text="ic"></option>
                                                            </template>
                                                        </select>
                                                        {{-- Upload image instead of an icon --}}
                                                        <div class="mt-1.5">
                                                            <label x-show="!card.image" class="inline-flex items-center gap-1 text-xs text-orange-600 cursor-pointer hover:underline">
                                                                <x-icon name="upload" class="w-3.5 h-3.5"/> Upload image
                                                                <input type="file" accept="image/*" class="hidden" @change="uploadIcon(card, $event)">
                                                            </label>
                                                            <div x-show="card.image" x-cloak class="flex items-center gap-2">
                                                                <img :src="card.image" alt="" class="w-8 h-8 object-contain rounded border border-gray-200 bg-white">
                                                                <button type="button" @click="clearIcon(card)" class="text-xs text-red-500 hover:text-red-600">Remove image</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs text-gray-500 mb-1">Title</label>
                                                        <input type="text" x-model="card.title" class="input-light text-sm" placeholder="Card title">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="block text-xs text-gray-500 mb-1">Sub header</label>
                                                    <input type="text" x-model="card.subheader" class="input-light text-sm" placeholder="Short tagline shown under the title">
                                                </div>
                                                <label class="block text-xs text-gray-500 mb-1">Description</label>
                                                <input type="hidden" :id="'cardbody-' + card.uid" :value="card.body">
                                                <trix-editor :input="'cardbody-' + card.uid"
                                                             @trix-change="card.body = $event.target.value"></trix-editor>
                                            </div>

                                            {{-- Live preview --}}
                                            <div>
                                                <div class="text-xs font-semibold text-gray-400 mb-2">Live preview</div>
                                                <div class="service-card max-w-xs mx-auto">
                                                    <div class="w-16 h-16 rounded-2xl bg-orange-100 flex items-center justify-center mb-6 overflow-hidden">
                                                        <img x-show="card.image" x-cloak :src="card.image" alt="" class="w-10 h-10 object-contain">
                                                        <span x-show="!card.image" x-cloak>
                                                            @foreach($iconChoices as $ic)
                                                                <span x-show="card.icon === '{{ $ic }}'">
                                                                    <x-icon name="{{ $ic }}" class="w-9 h-9 text-orange-600"/>
                                                                </span>
                                                            @endforeach
                                                        </span>
                                                    </div>
                                                    <h3 class="font-black text-2xl mb-3" x-text="card.title || 'Card title'"></h3>
                                                    <p x-show="card.subheader" x-cloak class="text-slate-500 text-sm mb-3" x-text="card.subheader"></p>
                                                    <div class="text-slate-600 text-[15px] mb-6 flex-1 cms-content" x-html="card.body"></div>
                                                    <span class="text-orange-600 font-semibold inline-flex items-center gap-1.5">
                                                        Request a Quote <x-icon name="arrow-right" class="w-4 h-4"/>
                                                    </span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p x-show="cardSets['{{ $key }}'].length === 0" x-cloak class="text-sm text-gray-400 mt-2">
                                No cards yet — click “Add card”.
                            </p>
                        </div>

                    @elseif($field['type'] === 'list')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $field['label'] }}</label>
                            <textarea data-cms-key="{{ $key }}" data-cms-type="list" rows="6"
                                      class="input-light font-mono text-sm leading-relaxed">{{ implode("\n", \App\Models\SiteContent::list($key)) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">One area per line.</p>
                        </div>

                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $field['label'] }}</label>
                            <input id="cms-{{ $key }}" type="hidden" data-cms-key="{{ $key }}" data-cms-type="html"
                                   value="{{ \App\Models\SiteContent::html($key) }}">
                            <trix-editor input="cms-{{ $key }}"></trix-editor>
                        </div>
                    @endif

                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Sticky, always-visible save bar --}}
    <div class="sticky bottom-0 z-10 flex items-center gap-3 rounded-lg border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
        <button @click="save()" :disabled="saving" class="btn-primary text-sm py-2.5 px-6">
            <span x-text="saving ? 'Saving…' : 'Save Changes'"></span>
        </button>
        <span x-show="saved" x-cloak class="text-emerald-600 text-sm font-medium">&check; Saved</span>
        <span x-show="error" x-text="error" x-cloak class="text-red-500 text-sm"></span>
        <span x-show="dirty && !saving && !saved" x-cloak class="ml-auto text-xs text-amber-600">Unsaved changes</span>
    </div>
</div>
