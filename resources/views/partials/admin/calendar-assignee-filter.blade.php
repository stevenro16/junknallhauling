{{-- Quick-select employee filter for the calendar. Multi-select buttons; selecting
     2+ employees splits the Day view into per-employee columns. Expects $employees. --}}
@if(count($employees ?? []) > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs text-gray-500 mr-0.5">Show:</span>
        <button type="button" @click="selectedAssignees = []"
                class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
                :class="selectedAssignees.length === 0 ? 'bg-gray-700 text-white border-gray-700' : 'bg-white border-gray-300 text-gray-600 hover:border-gray-400'">Everyone</button>
        @foreach($employees as $emp)
            <button type="button" @click="toggleAssignee('{{ $emp['id'] }}')"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
                    :class="assigneeSelected('{{ $emp['id'] }}') ? 'bg-amber-500 text-white border-amber-500' : 'bg-white border-gray-300 text-gray-700 hover:border-amber-400 hover:bg-amber-50'">{{ $emp['label'] }}</button>
        @endforeach
        <span x-show="columnMode" x-cloak class="text-[11px] text-amber-600 ml-1 inline-flex items-center gap-1"><x-icon name="check-circle" class="w-3 h-3"/> Columns by employee</span>
    </div>
@endif
