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
            'machine_code' => ['required', 'max:50', Rule::unique('machines')->ignore($id)], 'machine_name' => ['required', 'max:150'], 'machine_type' => ['required', 'max:100'], 'brand' => ['nullable', 'max:100'], 'model' => ['nullable', 'max:100'], 'serial_number' => ['nullable', 'max:100', Rule::unique('machines')->ignore($id)], 'asset_number' => ['nullable', 'max:100', Rule::unique('machines')->ignore($id)], 'customer_id' => ['required', 'exists:customers,id'],
            'installation_date' => ['nullable', 'date'], 'warranty_start' => ['nullable', 'date'], 'warranty_end' => ['nullable', 'date', 'after_or_equal:warranty_start'], 'amc_start' => ['nullable', 'date'], 'amc_end' => ['nullable', 'date', 'after_or_equal:amc_start'], 'service_frequency' => ['nullable', 'integer', 'min:1'], 'status' => ['required', Rule::in(['active', 'inactive', 'under_maintenance', 'decommissioned'])],
            'site_name' => ['nullable', 'max:150'], 'address' => ['nullable'], 'city' => ['nullable', 'max:100'], 'state' => ['nullable', 'max:100'], 'pin_code' => ['nullable', 'max:10'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'], 'notes' => ['nullable'],
            'documents.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'], 'document_type' => ['nullable', Rule::in(['photo', 'invoice', 'warranty', 'amc', 'manual', 'other'])],
        ];
    }
}
