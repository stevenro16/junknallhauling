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
            <div class="flex-1 relative {{ ($pickMode ?? false) ? 'cursor-crosshair' : '' }}"
                 @if($pickMode ?? false) x-ref="timeline" @click="pickTimeAt($event)" @pointermove.window="onDrag($event)" @pointerup.window="endDrag()" @pointercancel.window="endDrag()" @endif>
                <div class="absolute inset-0 grid grid-rows-[repeat(17,4rem)]">
                    <template x-for="hour in HOURS" :key="hour"><div class="relative border-b border-gray-200/60"><div class="absolute inset-x-0 top-1/2 border-b border-gray-100"></div></div></template>
                </div>
                @if($pickMode ?? false)
                    {{-- draggable preview card for the visit (sized to its duration) --}}
                    <div x-show="pickOnThisDay" x-cloak :style="pickCardStyle"
                         @pointerdown.stop.prevent="startMove($event)"
                         class="z-30 rounded-lg border-2 border-amber-500 bg-amber-400/30 shadow px-2 py-1 overflow-hidden cursor-move touch-none select-none"
                         :class="dragMode && 'ring-2 ring-amber-400'">
                        <div class="text-[11px] font-bold text-amber-800 truncate" x-text="pickLabel"></div>
                        <div class="text-[10px] text-amber-700/90 leading-tight" x-text="pickedTimeLabel + ' – ' + pickedEndLabel"></div>
                        {{-- resize handle (extend duration) --}}
                        <div @pointerdown.stop.prevent="startResize($event)"
                             class="absolute inset-x-0 bottom-0 h-4 flex items-end justify-center cursor-ns-resize touch-none">
                            <div class="w-8 h-1 mb-1 rounded-full bg-amber-600/80"></div>
                        </div>
                    </div>
                @endif
                <div x-show="dayLayout.length === 0" class="absolute inset-0 flex items-center justify-center"><p class="text-gray-500 text-sm">No pickups on this day</p></div>
                <template x-for="ev in dayLayout" :key="ev.inquiry.event_id">
                    <a :href="detailUrl(ev.inquiry.id)" target="{{ $linkTarget ?? '_self' }}" :style="{!! $evStyleExpr !!}" class="rounded-lg border transition-all overflow-hidden px-2 py-1 flex flex-col" :class="[eventClasses(ev.inquiry.status), ev.inquiry.type === 'pickup' ? 'border-dashed' : '']">
                        {{-- Text content: clips top-down; reserves bottom space for the always-visible assignee --}}
                        <div class="flex-1 min-h-0 overflow-hidden flex flex-col" :class="ev.inquiry.assigned_employee && 'pb-3.5'">
                            <div class="shrink-0 flex items-start justify-between gap-1 mt-0.5">
                                <div class="flex items-center gap-1 min-w-0">
                                    <div class="text-[10px] leading-none truncate min-w-0"><span class="font-mono text-amber-600" x-text="ev.inquiry.ref"></span><span class="text-gray-500" x-text="' - (' + fmtClock(ev.start) + ' - ' + fmtClock(ev.end) + ')'"></span></div>
                                    <span x-show="ev.inquiry.type === 'pickup'" x-cloak class="text-[9px] font-bold uppercase tracking-wide text-cyan-700 shrink-0">Pickup</span>
                                    <span x-show="ev.inquiry.before_visit" x-cloak title="Pickup is scheduled before the delivery visit" class="shrink-0"><x-icon name="alert" class="w-3 h-3 text-red-600"/></span>
                                </div>
                                {{-- Status bubble (top-right) --}}
                                <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-px rounded-full bg-white/95 border border-black/10 text-[8px] font-semibold text-gray-700 leading-none whitespace-nowrap"><span class="w-1.5 h-1.5 rounded-full" :class="dotClass(ev.inquiry.status)"></span><span x-text="statusLabel(ev.inquiry.status)"></span></span>
                            </div>
                            <div class="shrink-0 leading-tight truncate"><span class="font-semibold text-sm text-gray-900" x-text="ev.inquiry.name"></span><span class="text-gray-500 text-[10px] capitalize" x-text="' · ' + jobKind(ev.inquiry) + ' · ' + jobLabel(ev.inquiry)"></span></div>
                            <div x-show="ev.big && ev.inquiry.address" x-cloak class="shrink-0 flex items-start gap-0.5 text-gray-400 text-[10px] leading-tight truncate"><x-icon name="map-pin" class="w-2.5 h-2.5 shrink-0 mt-px"/><span class="truncate" x-text="ev.inquiry.address"></span></div>
                        </div>
                        {{-- Assigned-to — always pinned to the bottom-right corner --}}
                        <div x-show="ev.inquiry.assigned_employee" x-cloak class="absolute right-2 bottom-1 max-w-[calc(100%-1rem)] flex items-center gap-0.5 text-amber-700 text-[10px] leading-none"><x-icon name="user" class="w-2.5 h-2.5 shrink-0"/><span class="truncate" x-text="ev.inquiry.assigned_employee"></span></div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
