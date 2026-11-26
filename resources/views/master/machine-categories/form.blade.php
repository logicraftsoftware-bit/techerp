@extends('layouts.app', ['title' => $machineCategory->exists ? 'Edit Machine Category' : 'Add Machine Category'])

@section('content')
<form method="POST" action="{{ $machineCategory->exists ? route('machine-categories.update', $machineCategory) : route('machine-categories.store') }}" class="mx-auto max-w-xl">
    @csrf
    @if($machineCategory->exists) @method('PUT') @endif
    <div class="card p-7">
        <h2 class="text-2xl font-bold">{{ $machineCategory->exists ? 'Edit' : 'Add' }} Machine Category</h2>
        <div class="mt-6">@include('master._field', ['model' => $machineCategory, 'name' => 'category_name', 'label' => 'Category Name', 'required' => true])</div>
        <div class="mt-5" x-data>
            <label for="terms-and-conditions" class="form-label">Terms &amp; Conditions</label>
            <div class="mb-2 flex flex-wrap gap-2" role="group" aria-label="Terms and conditions formatting">
                <button type="button" class="table-action font-bold" @click="formatCategoryTerms($refs.terms, '**', '**')">Bold</button>
                <button type="button" class="table-action italic" @click="formatCategoryTerms($refs.terms, '*', '*')">Italic</button>
                <button type="button" class="table-action" @click="formatCategoryTerms($refs.terms, '## ', '', true)">Heading</button>
                <button type="button" class="table-action" @click="formatCategoryTerms($refs.terms, '- ', '', true)">Bullet list</button>
            </div>
            <textarea id="terms-and-conditions" name="terms_and_conditions" x-ref="terms" rows="10" class="form-input"
                aria-describedby="terms-format @error('terms_and_conditions') terms-error @enderror">{{ old('terms_and_conditions', $machineCategory->terms_and_conditions) }}</textarea>
            <p id="terms-format" class="mt-1 text-xs text-slate-500">Optional. Supports Markdown formatting.</p>
            @error('terms_and_conditions')
                <p id="terms-error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="mt-7 flex justify-end gap-3"><a href="{{ route('machine-categories.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Machine Category</button></div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function formatCategoryTerms(editor, prefix, suffix, wholeLines = false) {
    let start = editor.selectionStart;
    let end = editor.selectionEnd;
    if (wholeLines) {
        start = editor.value.slice(0, start).lastIndexOf('\n') + 1;
        const lineEnd = editor.value.indexOf('\n', end);
        end = lineEnd === -1 ? editor.value.length : lineEnd;
    }

    const selected = editor.value.slice(start, end);
    const formatted = wholeLines
        ? selected.split('\n').map(line => prefix + line).join('\n')
        : prefix + selected + suffix;

    editor.setRangeText(formatted, start, end, 'select');
    editor.focus();
    editor.setSelectionRange(start + prefix.length, start + formatted.length - suffix.length);
}
</script>
@endpush
