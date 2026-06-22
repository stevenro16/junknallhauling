{{-- Day-view timeline for the calendar component. Shared by the full calendar
     page and the embedded day popup. $linkTarget controls where event links open
     ('_self' on the full page, '_top' inside the iframe popup). $pickMode enables
     click-to-pick a time (used in the popup). When 2+ employees are selected the
     timeline splits into one column per employee (+ an Unassigned column). --}}
@php($evStyleExpr = ($pickMode ?? false) ? "ev.style + ';pointer-events:none'" : 'ev.style')
@php($cardData = ['evStyleExpr' => $evStyleExpr, 'linkTarget' => $linkTarget ?? '_self'])
<div x-show="viewMode === 'day'" x-cloak class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
    {{-- Per-employee column headers (columns mode only) --}}
    <div x-show="columnMode" x-cloak class="flex border-b border-gray-200 bg-gray-50">
        <div class="w-16 shrink-0"></div>
        <template x-for="col in dayAssigneeColumns" :key="col.id">
            <div class="flex-1 min-w-0 px-2 py-2 text-center border-l border-gray-200">
                <div class="text-xs font-semibold truncate inline-flex items-center gap-1 justify-center" :class="col.isUnassigned ? 'text-gray-400' : 'text-gray-700'">
                    <x-icon name="user" class="w-3 h-3 shrink-0"/><span class="truncate" x-text="col.name"></span>
                </div>
                <div class="text-[10px] text-gray-400"><span x-text="col.events.length"></span> visit<span x-show="col.events.length !== 1">s</span></div>
            </div>
        </template>
    </div>

    <div class="overflow-y-auto max-h-[75vh]">
        <div class="flex h-[1088px]">
            <div class="w-16 shrink-0 grid grid-rows-[repeat(17,4rem)] bg-white border-r border-gray-200 z-10">
                <template x-for="hour in HOURS" :key="hour">
                    <div class="flex items-start justify-end pr-2 pt-0.5 border-b border-gray-200/60">
                        <span class="text-[11px] text-gray-500 -translate-y-2.5 select-none whitespace-nowrap" x-text="formatHour(hour)"></span>
                    </div>
                </template>
            </div>
            <div class="flex-1 relative {{ (($pickMode ?? false) || ($createMode ?? false)) ? 'cursor-crosshair' : '' }}"
                 @if($pickMode ?? false) x-ref="timeline" @click="pickTimeAt($event)" @pointermove.window="onDrag($event)" @pointerup.window="endDrag()" @pointercancel.window="endDrag()" @endif
                 @if($createMode ?? false) @click="startNewQuoteAt($event, columnMode ? '' : (selectedAssignees.length === 1 ? selectedAssignees[0] : ''))" @endif>
                <div class="absolute inset-0 grid grid-rows-[repeat(17,4rem)]">
                    <template x-for="hour in HOURS" :key="hour"><div class="relative border-b border-gray-200/60"><div class="absolute inset-x-0 top-1/2 border-b border-gray-100"></div></div></template>
                </div>
                @if($pickMode ?? false)
                    {{-- draggable preview card for the visit (sized to its duration); full-width even in columns mode --}}
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

                {{-- Single timeline: 0 or 1 employee selected --}}
                <template x-if="!columnMode">
                    <div>
                        <div x-show="dayLayout.length === 0" class="absolute inset-0 flex items-center justify-center"><p class="text-gray-500 text-sm">No visits on this day</p></div>
                        <template x-for="ev in dayLayout" :key="ev.inquiry.event_id">
                            @include('partials.admin.calendar-event-card', $cardData)
                        </template>
                    </div>
                </template>

                {{-- Per-employee columns: 2+ employees selected --}}
                <div x-show="columnMode" x-cloak class="absolute inset-0 flex">
                    <template x-for="col in dayAssigneeColumns" :key="col.id">
                        <div class="flex-1 min-w-0 relative border-l border-gray-100 {{ ($createMode ?? false) ? 'cursor-crosshair' : '' }}"
                             @if($createMode ?? false) @click.stop="startNewQuoteAt($event, col.isUnassigned ? '' : col.id)" @endif>
                            <template x-for="ev in col.events" :key="ev.inquiry.event_id">
                                @include('partials.admin.calendar-event-card', $cardData)
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
