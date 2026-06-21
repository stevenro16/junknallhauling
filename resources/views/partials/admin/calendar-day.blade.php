{{-- Day-view timeline for the calendar component. Shared by the full calendar
     page and the embedded day popup. $linkTarget controls where event links open
     ('_self' on the full page, '_top' inside the iframe popup). $pickMode enables
     click-to-pick a time (used in the popup). --}}
@php($evStyleExpr = ($pickMode ?? false) ? "ev.style + ';pointer-events:none'" : 'ev.style')
<div x-show="viewMode === 'day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
    <div class="overflow-y-auto max-h-[75vh]">
        <div class="flex h-[1088px]">
            <div class="w-16 shrink-0 grid grid-rows-[repeat(17,4rem)] bg-white border-r border-gray-200 z-10">
                <template x-for="hour in HOURS" :key="hour">
                    <div class="flex items-start justify-end pr-2 pt-0.5 border-b border-gray-200/60">
                        <span class="text-[11px] text-gray-500 -translate-y-2.5 select-none whitespace-nowrap" x-text="formatHour(hour)"></span>
                    </div>
                </template>
            </div>
            <div class="flex-1 relative {{ ($pickMode ?? false) ? 'cursor-crosshair' : '' }}" @if($pickMode ?? false) @click="pickTimeAt($event)" @endif>
                <div class="absolute inset-0 grid grid-rows-[repeat(17,4rem)]">
                    <template x-for="hour in HOURS" :key="hour"><div class="relative border-b border-gray-200/60"><div class="absolute inset-x-0 top-1/2 border-b border-gray-100"></div></div></template>
                </div>
                @if($pickMode ?? false)
                    {{-- selected-time indicator --}}
                    <div x-show="pickIndicatorStyle" x-cloak :style="pickIndicatorStyle" class="z-20 pointer-events-none">
                        <div class="h-0.5 bg-amber-500"></div>
                        <div class="absolute -top-2.5 left-1 text-[10px] font-bold text-white bg-amber-500 px-1.5 py-0.5 rounded shadow" x-text="pickedTimeLabel"></div>
                    </div>
                @endif
                <div x-show="dayLayout.length === 0" class="absolute inset-0 flex items-center justify-center"><p class="text-gray-500 text-sm">No pickups on this day</p></div>
                <template x-for="ev in dayLayout" :key="ev.inquiry.id">
                    <a :href="detailUrl(ev.inquiry.id)" target="{{ $linkTarget ?? '_self' }}" :style="{!! $evStyleExpr !!}" class="rounded-lg border transition-all overflow-hidden px-2 py-1 flex flex-col" :class="eventClasses(ev.inquiry.status)">
                        <div class="flex items-center gap-1 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="dotClass(ev.inquiry.status)"></div>
                            <div class="font-mono text-amber-600 text-[10px] leading-none truncate" x-text="ev.inquiry.ref"></div>
                        </div>
                        <div class="font-semibold text-sm text-gray-900 leading-tight truncate" x-text="ev.inquiry.name"></div>
                        <div class="text-gray-500 text-[10px] leading-tight"><span x-text="fmtClock(ev.start)"></span> &ndash; <span x-text="fmtClock(ev.end)"></span></div>
                        <div x-show="ev.big" class="text-gray-400 text-[10px] leading-tight truncate capitalize"><span x-text="statusLabel(ev.inquiry.status)"></span> &middot; <span x-text="serviceLabel(ev.inquiry.service_type)"></span></div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
