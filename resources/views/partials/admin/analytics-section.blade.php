<div x-data="analytics({ inquiries: @js($statsInquiries) })">
    {{-- Range toggle --}}
    <div class="flex items-center justify-between gap-3 mb-4">
        <p class="text-xs text-gray-400 hidden sm:block">Metrics below reflect quotes created in the selected range.</p>
        <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm shrink-0">
            <button @click="setRange('30')" class="px-4 py-1.5 transition-colors" :class="range === '30' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">Last 30 Days</button>
            <button @click="setRange('mtd')" class="px-4 py-1.5 transition-colors border-l border-gray-300" :class="range === 'mtd' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">MTD</button>
            <button @click="setRange('ytd')" class="px-4 py-1.5 transition-colors border-l border-gray-300" :class="range === 'ytd' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">YTD</button>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Actively Scheduled</div><div class="text-3xl font-black text-purple-600 mt-1" x-text="scheduledCount"></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Completed</div><div class="text-3xl font-black text-green-700 mt-1" x-text="completedCount"></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Total Revenue</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(revenue)"></span></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Avg Job Value</div><div class="text-3xl font-black text-emerald-600 mt-1">$<span x-text="money(avgJobValue)"></span></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Quoted &mdash; Unpaid</div><div class="text-3xl font-black text-amber-600 mt-1">$<span x-text="money(quotedUnpaid)"></span></div></div>
        <div class="card-light p-4"><div class="text-xs uppercase tracking-widest text-gray-500">Conversion</div><div class="text-3xl font-black text-blue-600 mt-1"><span x-text="conversionRate"></span>%</div></div>
    </div>

    {{-- Services vs Equipment Rental — split + drill-down --}}
    <div class="card-light p-5 mb-6">
        <div class="text-sm font-semibold text-gray-700 mb-4">Services vs Equipment Rental</div>

        {{-- Selectable category tiles (revenue collected + job count) --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <button type="button" @click="category = 'services'"
                    class="text-left rounded-xl border-2 p-4 transition-colors"
                    :class="category === 'services' ? 'border-amber-400 bg-amber-50/60' : 'border-gray-200 hover:border-gray-300'">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-widest text-gray-500">Services</span>
                    <span class="text-[10px] text-gray-400" x-text="servicesTotal.jobs + (servicesTotal.jobs === 1 ? ' job' : ' jobs')"></span>
                </div>
                <div class="text-2xl font-black text-emerald-600 mt-1">$<span x-text="money(servicesTotal.revenue)"></span></div>
                <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" :style="`width:${Math.round(servicesTotal.revenue / categoryRevenueMax * 100)}%`"></div>
                </div>
            </button>
            <button type="button" @click="category = 'equipment'"
                    class="text-left rounded-xl border-2 p-4 transition-colors"
                    :class="category === 'equipment' ? 'border-amber-400 bg-amber-50/60' : 'border-gray-200 hover:border-gray-300'">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-widest text-gray-500">Equipment Rental</span>
                    <span class="text-[10px] text-gray-400" x-text="equipmentTotal.jobs + (equipmentTotal.jobs === 1 ? ' job' : ' jobs')"></span>
                </div>
                <div class="text-2xl font-black text-indigo-600 mt-1">$<span x-text="money(equipmentTotal.revenue)"></span></div>
                <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" :style="`width:${Math.round(equipmentTotal.revenue / categoryRevenueMax * 100)}%`"></div>
                </div>
            </button>
        </div>

        {{-- Drill-down: per-item breakdown for the selected category --}}
        <div class="flex items-center justify-between mb-2">
            <div class="text-xs font-semibold uppercase tracking-widest text-gray-500" x-text="(category === 'equipment' ? 'Equipment' : 'Service') + ' breakdown'"></div>
            <div class="text-[10px] text-gray-400">Bar = revenue collected · avg per completed job</div>
        </div>
        <div class="space-y-3">
            <template x-for="row in activeRows" :key="row.key">
                <div>
                    <div class="flex items-center justify-between text-xs mb-1 gap-2">
                        <span class="text-gray-700 font-medium truncate" x-text="row.label"></span>
                        <span class="text-gray-500 shrink-0">
                            <span x-text="row.jobs"></span> <span x-text="row.jobs === 1 ? 'job' : 'jobs'"></span>
                            <span class="text-gray-300">·</span>
                            <span class="font-semibold text-emerald-600">$<span x-text="money(row.revenue)"></span></span>
                            <span x-show="row.avg" class="text-gray-400">(avg $<span x-text="money(row.avg)"></span>)</span>
                        </span>
                    </div>
                    <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full" :class="category === 'equipment' ? 'bg-indigo-500' : 'bg-emerald-500'" :style="`width:${Math.max(2, row.pct)}%`"></div>
                    </div>
                </div>
            </template>
            <div x-show="activeRows.length === 0" class="text-sm text-gray-400 py-6 text-center">No <span x-text="category === 'equipment' ? 'equipment rentals' : 'services'"></span> in this range.</div>
        </div>
    </div>

    {{-- Revenue collected timeline (trailing 12 weeks/months, independent of range) --}}
    <div class="card-light p-5 mb-6">
        <div class="flex items-center justify-between gap-3 mb-1">
            <div class="text-sm font-semibold text-gray-700">Revenue Collected</div>
            <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-xs shrink-0">
                <button @click="timelineMode = 'week'" class="px-3 py-1 transition-colors" :class="timelineMode === 'week' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">Weekly</button>
                <button @click="timelineMode = 'month'" class="px-3 py-1 transition-colors border-l border-gray-300" :class="timelineMode === 'month' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100'">Monthly</button>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mb-4">Last 12 <span x-text="timelineMode === 'week' ? 'weeks' : 'months'"></span> · total $<span x-text="money(timelineTotal)"></span></p>

        <div class="flex items-end gap-1.5 h-44">
            <template x-for="p in timeline" :key="p.label">
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <div class="text-[9px] text-gray-500 mb-1 h-3" x-text="p.revenue > 0 ? '$' + moneyShort(p.revenue) : ''"></div>
                    <div class="w-full bg-emerald-500/80 hover:bg-emerald-500 rounded-t transition-all" :style="`height:${Math.max(2, p.pct)}%`" :title="'$' + money(p.revenue)"></div>
                </div>
            </template>
        </div>
        <div class="flex gap-1.5 mt-1 border-t border-gray-100 pt-1">
            <template x-for="p in timeline" :key="'l-' + p.label">
                <div class="flex-1 text-center text-[9px] text-gray-400 truncate" x-text="p.label"></div>
            </template>
        </div>
    </div>

    {{-- Pipeline + payment method --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Pipeline (status mix) --}}
        <div class="card-light p-5">
            <div class="text-sm font-semibold text-gray-700 mb-4">Pipeline</div>
            <div class="space-y-3">
                <template x-for="row in statusBreakdown" :key="row.key">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-600" x-text="row.label"></span>
                            <span class="font-semibold text-gray-800" x-text="row.count"></span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-[#EAB308] rounded-full" :style="`width:${Math.max(2, row.pct)}%`"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Revenue by payment method --}}
        <div class="card-light p-5">
            <div class="text-sm font-semibold text-gray-700 mb-4">Revenue by Payment Method</div>
            <div class="space-y-3">
                <template x-for="row in paymentBreakdown" :key="row.label">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-600" x-text="row.label + ' (' + row.count + ')'"></span>
                            <span class="font-semibold text-emerald-600">$<span x-text="money(row.revenue)"></span></span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" :style="`width:${Math.max(2, row.pct)}%`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="paymentBreakdown.length === 0" class="text-sm text-gray-400 py-6 text-center">No payments recorded in this range.</div>
            </div>
        </div>
    </div>

    {{-- Job locations map --}}
    <div class="card-light p-5">
        <div class="text-sm font-semibold text-gray-700 mb-4">Job Locations</div>
        <div x-ref="map" class="h-96 rounded-lg overflow-hidden border border-gray-200 z-0"></div>
        <p class="text-[10px] text-gray-400 mt-2">Active pipeline jobs (new through service-performed). Positions are approximate.</p>
    </div>
</div>
