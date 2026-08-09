<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('machine')?->id;

        return [
            'machine_name' => ['required', 'string', 'max:150'],
            'brand_id' => ['required', 'exists:brands,id'],
            'model' => ['required', 'string', 'max:100'],
            'serial_number' => ['nullable', 'max:100', Rule::unique('machines')->ignore($id)],
            'asset_number' => ['nullable', 'max:100', Rule::unique('machines')->ignore($id)],
            'manufacturing_date' => ['nullable', 'date', 'before_or_equal:today'],
            'service_period' => ['required', Rule::in(['4_months', '6_months', '1_year', '2_years'])],
            'buying_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'location_name' => ['required', 'string', 'max:190'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'machine_photos' => ['nullable', 'array', 'max:10'],
            'machine_photos.*' => ['file', 'mimetypes:image/*', 'max:5120'],
            'warranty_card' => ['nullable', 'file', 'mimetypes:image/*,application/pdf', 'max:10240'],
            'service_coupon' => ['nullable', 'file', 'mimetypes:image/*,application/pdf', 'max:10240'],
        ];
    }
}
