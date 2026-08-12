@php
    $selectedValue = (string) old($name, $selected ?? '');
    $selectOptions = collect($options)->map(fn ($option) => [
        'value' => (string) data_get($option, $valueKey ?? 'id'),
        'label' => (string) data_get($option, $labelKey),
    ])->values();
    $selectedLabel = $selectOptions->firstWhere('value', $selectedValue)['label'] ?? '';
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        search: @js($selectedLabel),
        selected: @js($selectedValue),
        options: @js($selectOptions),
        get filtered() {
            const term = this.search.toLowerCase().trim();
            return term && !this.selected
                ? this.options.filter(option => option.label.toLowerCase().includes(term))
                : this.options;
        },
        choose(option) {
            this.selected = option.value;
            this.search = option.label;
            this.open = false;
        },
        clearSelection() {
            this.selected = '';
            this.open = true;
        }
    }"
    @click.outside="open = false"
>
    <label class="form-label">{{ $label }} @if($required ?? false)*@endif</label>
    <input type="hidden" name="{{ $name }}" x-model="selected">
    <div class="relative">
        <input
            type="search"
            x-model="search"
            @focus="open = true"
            @click="open = true"
            @input="clearSelection()"
            @keydown.escape="open = false"
            @keydown.arrow-down.prevent="open = true"
            class="form-input pr-10"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            role="combobox"
            :aria-expanded="open"
        >
        <button type="button" @click="open = !open" class="absolute inset-y-0 right-0 px-3 text-slate-400" aria-label="Toggle {{ strtolower($label) }} options">⌄</button>
    </div>
    <div x-cloak x-show="open" x-transition class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <template x-for="option in filtered" :key="option.value">
            <button type="button" @click="choose(option)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-blue-50 hover:text-blue-700" :class="selected === option.value && 'bg-blue-50 font-semibold text-blue-700'" x-text="option.label"></button>
        </template>
        <p x-show="filtered.length === 0" class="px-3 py-4 text-center text-sm text-slate-400">No matching option found.</p>
    </div>
</div>
