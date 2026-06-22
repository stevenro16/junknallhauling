{{-- Multi-select assignee control — quick-select buttons (one per option) that
     toggle membership in an array property. $model = the Alpine array property to
     bind (e.g. 'assignedEmployeeIds'). Expects $employees in scope. --}}
<div class="flex flex-wrap gap-2">
    <button type="button" @click="{{ $model }} = []"
            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
            :class="{{ $model }}.length === 0 ? 'bg-gray-600 text-white border-gray-600' : 'bg-white border-gray-300 text-gray-600 hover:border-gray-400'">Unassigned</button>
    @foreach($employees as $emp)
        <button type="button" @click="toggleAssignee('{{ $model }}', '{{ $emp['id'] }}')"
                class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors inline-flex items-center gap-1.5"
                :class="assigneeChecked('{{ $model }}', '{{ $emp['id'] }}') ? 'bg-amber-500 text-white border-amber-500' : 'bg-white border-gray-300 text-gray-700 hover:border-amber-400 hover:bg-amber-50'">
            <x-icon name="check" class="w-3.5 h-3.5" x-show="assigneeChecked('{{ $model }}', '{{ $emp['id'] }}')" x-cloak/>
            <span>{{ $emp['label'] }}</span>
        </button>
    @endforeach
</div>
