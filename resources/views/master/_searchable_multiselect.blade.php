@php
    $multiOptions = collect($options)->values();
    $multiSelected = array_map('strval', (array) request($name, []));
@endphp
<div class="filter-multi" x-data="{
    open: false, search: '', options: @js($multiOptions), selected: @js($multiSelected),
    get filtered() { const term = this.search.trim().toLowerCase(); return this.options.filter(item => item.label.toLowerCase().includes(term)); },
    get summary() { const labels = this.options.filter(item => this.selected.includes(String(item.value))).map(item => item.label); return labels.length ? labels.join(', ') : 'All'; },
    selectVisible() { this.selected = [...new Set([...this.selected, ...this.filtered.map(item => String(item.value))])]; }
}" @click.outside="open = false" @keydown.escape.stop="open = false; $refs.trigger.focus()">
    <label class="form-label" for="filter-{{ $name }}">{{ $label }}</label>
    <template x-for="value in selected" :key="value"><input type="hidden" name="{{ $name }}[]" :value="value"></template>
    <button id="filter-{{ $name }}" x-ref="trigger" type="button" class="form-input filter-multi-trigger" @click="open = !open; if (open) $nextTick(() => $refs.search.focus())" :aria-expanded="open" aria-controls="filter-options-{{ $name }}">
        <span class="filter-multi-summary" x-text="summary" :title="summary"></span><span aria-hidden="true">&#9662;</span>
    </button>
    <div id="filter-options-{{ $name }}" class="filter-multi-menu" x-cloak x-show="open">
        <input x-ref="search" type="search" x-model="search" class="form-input" placeholder="Search {{ strtolower($label) }}..." aria-label="Search {{ $label }}" @keydown.enter.prevent>
        <div class="filter-multi-tools"><button type="button" @click="selectVisible()">Select matching</button><button type="button" @click="selected = []">Clear all</button></div>
        <div class="filter-multi-options">
            <template x-for="option in filtered" :key="option.value"><label class="filter-multi-option"><input type="checkbox" :value="String(option.value)" x-model="selected"><span x-text="option.label"></span></label></template>
            <p x-show="filtered.length === 0" class="p-4 text-sm text-slate-400">No matching options.</p>
        </div>
        <div class="filter-multi-tools"><span x-text="selected.length + ' selected'"></span><button type="button" @click="open = false; $refs.trigger.focus()">Done</button></div>
    </div>
</div>
