@extends('layouts.app',['title'=>$machine->exists?'Edit Machine':'Add Machine'])
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{$machine->exists?route('machines.update',$machine):route('machines.store')}}" class="mx-auto max-w-5xl">@csrf @if($machine->exists)@method('PUT')@endif
<div class="mb-6 flex justify-between"><div><h2 class="text-2xl font-bold">{{$machine->exists?'Edit':'Add'}} Machine</h2><p class="text-sm text-slate-500">Machine identity, pricing, stock, location and documents.</p></div><button class="btn-primary">Save Machine</button></div>
<section class="card p-6"><div class="grid gap-5 md:grid-cols-2">
<div><label class="form-label">Machine Code</label><input class="form-input bg-slate-50" value="{{$machine->machine_code?:'Auto-generated after saving'}}" readonly><p class="mt-1 text-xs text-slate-400">First two letters of machine name + six random digits</p></div>
@include('master._field',['model'=>$machine,'name'=>'machine_name','label'=>'Machine Name','required'=>true])
<div>@include('master._searchable_select', ['name' => 'brand_id', 'label' => 'Brand Name', 'options' => $brands, 'labelKey' => 'brand_name', 'selected' => $machine->brand_id, 'placeholder' => 'Search or select brand', 'required' => true])<a class="mt-1 inline-block text-xs text-blue-600" href="{{route('brands.create')}}">+ Add brand</a></div>
<div>@include('master._searchable_select', ['name' => 'machine_category_id', 'label' => 'Machine Category', 'options' => $categories, 'labelKey' => 'category_name', 'selected' => $machine->machine_category_id, 'placeholder' => 'Search or select category', 'required' => true])<a class="mt-1 inline-block text-xs text-blue-600" href="{{route('machine-categories.create')}}">+ Add category</a></div>
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
@if($machine->exists && $machine->documents->isNotEmpty())
<section class="card mt-6 p-6" id="existing-uploads">
    <h3 class="font-bold">Existing uploads</h3>
    <div class="mt-4 flex flex-wrap gap-3">
        @foreach($machine->documents as $document)
            @php
                $uploadLabel = match ($document->document_type) {
                    'photo' => 'Machine Photo',
                    'warranty' => 'Warranty Card',
                    'service_coupon' => 'Service Coupon',
                    default => str($document->document_type)->replace('_', ' ')->title(),
                };
            @endphp
            <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white" style="width: 170px;" data-upload-card>
                <button type="button" class="absolute right-2 top-2 z-10 flex items-center justify-center rounded-full bg-red-600 text-lg leading-none text-white shadow hover:bg-red-700" style="width: 24px; height: 24px;" aria-label="Delete {{ $document->original_name }}" title="Delete upload" data-delete-upload="{{ route('machine-documents.destroy', $document) }}">&times;</button>
                <a href="{{ route('machine-documents.show', $document) }}" target="_blank" class="block">
                    @if(str_starts_with($document->mime_type ?? '', 'image/'))
                        <img src="{{ route('machine-documents.show', $document) }}" alt="{{ $uploadLabel }}" class="w-full bg-slate-100 object-cover" style="height: 105px;" loading="lazy">
                    @else
                        <div class="flex items-center justify-center bg-slate-100 text-xs font-semibold text-slate-500" style="height: 105px;">Open document</div>
                    @endif
                </a>
                <div class="p-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $uploadLabel }}</p>
                    <p class="mt-1 truncate text-xs text-slate-700" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
<script>
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-delete-upload]');
    if (!button) return;
    const confirmation = await window.Swal.fire({ icon: 'warning', title: 'Delete upload?', text: 'This uploaded file will be deleted permanently.', showCancelButton: true, confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel', confirmButtonColor: '#dc2626', reverseButtons: true });
    if (!confirmation.isConfirmed) return;

    button.disabled = true;
    try {
        const response = await fetch(button.dataset.deleteUpload, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        if (!response.ok) throw new Error('Delete failed');
        button.closest('[data-upload-card]').remove();
        window.Swal.fire({ icon: 'success', title: 'Deleted', text: 'Upload deleted successfully.', timer: 1600, showConfirmButton: false });
    } catch (error) {
        button.disabled = false;
        window.Swal.fire({ icon: 'error', title: 'Delete failed', text: 'Could not delete this upload. Please try again.' });
    }
});
</script>
@endif
<div class="mt-6 flex justify-end gap-3"><a href="{{route('machines.index')}}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Machine</button></div></form>
@endsection
