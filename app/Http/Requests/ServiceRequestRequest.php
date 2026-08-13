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
        return [
            'request_type' => ['required', Rule::in(['new_installation', 'existing_service'])],
            'service_type' => ['required', Rule::in(['installation', 'amc', 'free_service', 'paid_service'])],
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'machine_id' => ['required', 'exists:machines,id'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'asset_number' => ['nullable', 'string', 'max:100'],
            'amc_plan_ids' => ['nullable', 'array'],
            'amc_plan_ids.*' => ['integer', 'distinct', 'exists:amc_plans,id'],
            'subject' => ['required', 'string', 'max:190'],
            'complaint' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'service_address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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
