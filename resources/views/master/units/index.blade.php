@extends('layouts.app', ['title' => 'Unit Master'])

@section('content')
@include('master._index_header', ['heading' => 'Unit Master', 'description' => 'Stock units used by Parts Master.', 'create' => route('units.create'), 'singular' => 'Unit'])

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Unit Name</th><th class="p-4 text-right">Actions</th></tr></thead>
        <tbody class="divide-y">
            @forelse($records as $unit)
                <tr>
                    <td class="p-4 font-semibold text-slate-800">{{ $unit->unit_name }}</td>
                    <td class="p-4"><div class="flex justify-end gap-2"><a class="table-action" href="{{ route('units.edit', $unit) }}">Edit</a><form method="POST" action="{{ route('units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="2" class="p-12 text-center text-slate-400">No units found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
