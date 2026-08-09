@extends('layouts.app',['title'=>$machine->exists?'Edit Machine':'Add Machine'])
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{$machine->exists?route('machines.update',$machine):route('machines.store')}}" class="mx-auto max-w-5xl">@csrf @if($machine->exists)@method('PUT')@endif
<div class="mb-6 flex justify-between"><div><h2 class="text-2xl font-bold">{{$machine->exists?'Edit':'Add'}} Machine</h2><p class="text-sm text-slate-500">Machine identity, pricing, stock, location and documents.</p></div><button class="btn-primary">Save Machine</button></div>
<section class="card p-6"><div class="grid gap-5 md:grid-cols-2">
<div><label class="form-label">Machine Code</label><input class="form-input bg-slate-50" value="{{$machine->machine_code?:'Auto-generated after saving'}}" readonly><p class="mt-1 text-xs text-slate-400">First two letters of machine name + six random digits</p></div>
@include('master._field',['model'=>$machine,'name'=>'machine_name','label'=>'Machine Name','required'=>true])
<div><label class="form-label">Brand Name *</label><select name="brand_id" class="form-input" required><option value="">Select brand</option>@foreach($brands as $brand)<option value="{{$brand->id}}" @selected(old('brand_id',$machine->brand_id)==$brand->id)>{{$brand->brand_name}}</option>@endforeach</select><a class="mt-1 inline-block text-xs text-blue-600" href="{{route('brands.create')}}">+ Add brand</a></div>
@include('master._field',['model'=>$machine,'name'=>'model','label'=>'Model Name','required'=>true])
@include('master._field',['model'=>$machine,'name'=>'serial_number','label'=>'Serial Number']) @include('master._field',['model'=>$machine,'name'=>'asset_number','label'=>'Asset Number'])
@include('master._field',['model'=>$machine,'name'=>'manufacturing_date','label'=>'Mfg Date','type'=>'date'])
@include('master._field',['model'=>$machine,'name'=>'service_period','label'=>'Free Service Period','type'=>'select','required'=>true,'options'=>['4_months'=>'4 Months','6_months'=>'6 Months','1_year'=>'1 Year','2_years'=>'2 Years']])
@include('master._field',['model'=>$machine,'name'=>'buying_price','label'=>'Buying Price','type'=>'number','required'=>true]) @include('master._field',['model'=>$machine,'name'=>'selling_price','label'=>'Selling Price','type'=>'number','required'=>true])
@include('master._field',['model'=>$machine,'name'=>'total_stock','label'=>'Total Stock','type'=>'number','required'=>true]) @include('master._field',['model'=>$machine,'name'=>'location_name','label'=>'Machine Location (Location Name)','required'=>true])
@include('master._field',['model'=>$machine,'name'=>'status','label'=>'Status','type'=>'select','required'=>true,'options'=>['active'=>'Active','inactive'=>'Inactive']])
<div><label class="form-label">Machine Photos</label><input class="form-input" type="file" name="machine_photos[]" accept="image/*" multiple><p class="mt-1 text-xs text-slate-400">Any image format, up to 10 photos (5 MB each).</p></div>
<div><label class="form-label">Warranty Card</label><input class="form-input" type="file" name="warranty_card" accept="image/*,application/pdf"><p class="mt-1 text-xs text-slate-400">Any image format or PDF (10 MB maximum).</p></div>
<div><label class="form-label">Service Coupon</label><input class="form-input" type="file" name="service_coupon" accept="image/*,application/pdf"><p class="mt-1 text-xs text-slate-400">Any image format or PDF (10 MB maximum).</p></div>
</div></section>
@if($machine->exists && $machine->documents->isNotEmpty())<section class="card mt-6 p-6"><h3 class="font-bold">Existing uploads</h3><div class="mt-4 flex flex-wrap gap-2">@foreach($machine->documents as $document)<a class="btn-secondary" target="_blank" href="{{route('machine-documents.show',$document)}}">{{$document->original_name}}</a>@endforeach</div></section>@endif
<div class="mt-6 flex justify-end gap-3"><a href="{{route('machines.index')}}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Machine</button></div></form>
@endsection
