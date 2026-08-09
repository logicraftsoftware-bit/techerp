<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $records = Brand::withCount('machines')->when($request->search, fn ($q, $s) => $q->where('brand_name', 'like', "%$s%"))->orderBy('brand_name')->paginate(15)->withQueryString();

        return view('master.brands.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.brands.form', ['brand' => new Brand]);
    }

    public function store(BrandRequest $r): RedirectResponse
    {
        Brand::create($r->validated());

        return to_route('brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('master.brands.form', compact('brand'));
    }

    public function update(BrandRequest $r, Brand $brand): RedirectResponse
    {
        $brand->update($r->validated());

        return to_route('brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        abort_if($brand->machines()->exists(), 422, 'This brand is used by machines.');
        $brand->delete();

        return back()->with('success','Brand deleted.');
    }
}
