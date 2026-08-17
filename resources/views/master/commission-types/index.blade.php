@extends('layouts.app', ['title' => 'Commission Type Master'])

@section('content')
@include('master._index_header', ['heading' => 'Commission Type Master', 'description' => 'Commission types used in salary structure.', 'create' => route('commission-types.create'), 'singular' => 'Commission Type'])

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Type Name</th><th class="p-4">Type</th><th class="p-4">Amount / Percentage</th><th class="p-4 text-right">Actions</th></tr></thead>
        <tbody class="divide-y">
            @forelse($records as $commissionType)
                <tr>
                    <td class="p-4 font-semibold text-slate-800">{{ $commissionType->type_name }}</td>
                    <td class="p-4 capitalize">{{ $commissionType->calculation_type }}</td>
                    <td class="p-4">{{ $commissionType->calculation_type === 'percentage' ? rtrim(rtrim(number_format((float) $commissionType->value, 2), '0'), '.').'%' : '₹'.number_format((float) $commissionType->value, 2) }}</td>
                    <td class="p-4"><div class="flex justify-end gap-2"><a class="table-action" href="{{ route('commission-types.edit', $commissionType) }}">Edit</a><form method="POST" action="{{ route('commission-types.destroy', $commissionType) }}" onsubmit="return confirm('Delete this commission type?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-12 text-center text-slate-400">No commission types found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
