<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $existing = $this->input('request_type') === 'existing_service';

        return [
            'request_type' => ['required', Rule::in(['new_installation', 'existing_service'])],
            'service_type' => ['required', Rule::in(['installation', 'amc', 'free_service', 'paid_service'])],
            'customer_id' => ['required', 'exists:customers,id'],
            'machine_id' => [$existing ? 'required' : 'nullable', 'exists:machines,id'],
            'machine_category_id' => [$existing ? 'nullable' : 'required', 'exists:machine_categories,id'],
            'brand_id' => [$existing ? 'nullable' : 'required', 'exists:brands,id'],
            'product_name' => [$existing ? 'nullable' : 'required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:190'],
            'complaint' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'service_address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pin_code' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['open', 'scheduled', 'in_progress', 'completed', 'cancelled'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('request_type') === 'new_installation' && $this->input('service_type') !== 'installation') {
                $validator->errors()->add('service_type', 'New requests must use Installation service type.');
            }

            if ($this->input('request_type') === 'existing_service' && $this->input('service_type') === 'installation') {
                $validator->errors()->add('service_type', 'Choose AMC, Free Service, or Paid Service for an existing machine.');
            }
        });
    }
}
