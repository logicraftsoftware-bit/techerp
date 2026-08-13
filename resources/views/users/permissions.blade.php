@extends('layouts.app', ['title' => 'Permissions · '.$user->name])

@section('content')
<form method="POST" action="{{ route('users.permissions.update', $user) }}" class="mx-auto max-w-5xl">
    @csrf
    @method('PUT')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><h2 class="text-2xl font-bold text-slate-900">Permissions</h2><p class="mt-1 text-sm text-slate-500">Choose exactly which menus {{ $user->name }} can view, add, edit and delete in.</p></div>
        <div class="flex gap-3"><a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Permissions</button></div>
    </div>

    @foreach($menuGroups as $group => $items)
        <section class="card mb-5 overflow-x-auto" data-permission-section>
            <div class="flex items-center justify-between border-b border-slate-100 p-4">
                <h3 class="font-bold text-slate-900">{{ $group }}</h3>
                <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500"><input type="checkbox" class="rounded" data-select-all onchange="this.closest('[data-permission-section]').querySelectorAll('tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked)"> Select All</label>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="p-4">Menu</th>
                        @foreach($actions as $action)<th class="p-4 text-center">{{ ucfirst($action) }}</th>@endforeach
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($items as [$slug, $label, $description])
                        <tr>
                            <td class="p-4"><span class="block font-medium text-slate-800">{{ $label }}</span><span class="block text-xs text-slate-400">{{ $description }}</span></td>
                            @foreach($actions as $action)
                                <td class="p-4 text-center"><input type="checkbox" name="permissions[{{ $slug }}][{{ $action }}]" value="1" class="rounded" @checked(in_array("$slug.$action", $granted))></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach

    <div class="flex justify-end gap-3"><a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Permissions</button></div>
</form>
@endsection
