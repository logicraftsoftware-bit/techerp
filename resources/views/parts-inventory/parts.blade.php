@extends('layouts.app',['title'=>'Parts Master']) @section('content')<div class="mb-6 flex items-end justify-between gap-4"><div><h2 class="text-2xl font-bold">Parts Master</h2><p class="mt-1 text-sm text-slate-500">Catalog, pricing, warranty and stock thresholds.</p></div><div class="flex gap-3"><a href="{{route('parts.import.create')}}" class="btn-secondary">Bulk Upload</a><a href="{{route('parts.create')}}" class="btn-primary">+ Add Part</a></div></div>
@if(session('import_errors') && count(session('import_errors')))
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold">Some rows from your last import were skipped:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
@include('parts-inventory._table',['headers'=>['Code','Part','Category','Unit','Stock','Dealer Price','MRP','AMC / Warranty','Status'],'rows'=>$records->map(fn($x)=>[$x->part_code,$x->part_name,$x->machineCategory?->category_name,$x->unitMaster?->unit_name,$x->current_stock,$x->purchase_price,$x->selling_price,($x->has_amc?'AMC':'—').' / '.($x->has_warranty?$x->warranty_months.' mo':'—'),$x->status])])@endsection
