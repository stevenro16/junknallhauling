<div x-data="analytics({ inquiries: @js($statsInquiries) })">
    {{-- Range toggle --}}
    <div class="flex items-center justify-end mb-4">
        <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
            <button @click="setRange('30')" class="px-4 py-1.5 transition-colors" :class="range === '30' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">Last 30 Days</button>
            <button @click="setRange('mtd')" class="px-4 py-1.5 transition-colors border-l border-gray-300" :class="range === 'mtd' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">MTD</button>
            <button @click="setRange('ytd')" class="px-4 py-1.5 transition-colors border-l border-gray-300" :class="range === 'ytd' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">YTD</button>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Actively Scheduled</div><div class="text-3xl font-black text-purple-600 mt-1" x-text="scheduledCount"></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Completed</div><div class="text-3xl font-black text-green-700 mt-1" x-text="completedCount"></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Total Revenue</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(revenue)"></span></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Avg Job Value</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(avgJobValue)"></span></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Quoted &mdash; Unpaid</div><div class="text-3xl font-black text-amber-600 mt-1">$<span x-text="money(quotedUnpaid)"></span></div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Avg fee by service --}}
        <div class="card-light p-5">
            <div class="text-sm font-semibold text-gray-700 mb-4">Average Fee by Service</div>
            <div class="space-y-3">
                <template x-for="row in serviceBreakdown" :key="row.key">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-600" x-text="row.label + ' (' + row.count + ')'"></span>
                            <span class="font-semibold text-gray-800">$<span x-text="money(row.avg)"></span></span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-[#EAB308] rounded-full" :style="`width:${row.pct}%`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="serviceBreakdown.length === 0" class="text-sm text-gray-400 py-6 text-center">No data in this range.</div>
            </div>
        </div>

        {{-- Job locations map --}}
        <div class="card-light p-5">
            <div class="text-sm font-semibold text-gray-700 mb-4">Job Locations</div>
            <div x-ref="map" class="h-96 rounded-lg overflow-hidden border border-gray-200 z-0"></div>
            <p class="text-[10px] text-gray-400 mt-2">Active pipeline jobs (new through service-performed). Positions are approximate.</p>
        </div>
    </div>
</div>
