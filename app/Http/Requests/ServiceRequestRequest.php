<?php

namespace App\Http\Requests;

use App\Models\CustomerAmcTagging;
use App\Models\ServiceRequest;
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
            'referred_by' => ['nullable', 'regex:/^(technician|user):\d+$/'],
            'customer_amc_tagging_id' => ['nullable', 'integer', 'exists:customer_amc_taggings,id'],
            'amc_service_number' => ['nullable', 'integer', 'min:1'],
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

            if (! $this->route('service_request') && $this->input('request_type') === 'existing_service' && ! $this->filled('customer_amc_tagging_id')) {
                $validator->errors()->add('request_type', 'Existing machine services must be created from a Customer-AMC Tagging service slot.');
            }

            if ($this->filled('customer_amc_tagging_id')) {
                $tagging = CustomerAmcTagging::find($this->integer('customer_amc_tagging_id'));
                $slot = $this->integer('amc_service_number');

                if (! $tagging || $slot < 1 || $slot > $tagging->service_count) {
                    $validator->errors()->add('amc_service_number', 'The selected AMC service slot is invalid.');
                } elseif ((int) $this->input('customer_id') !== $tagging->customer_id
                    || (int) $this->input('machine_id') !== $tagging->machine_id
                    || $this->input('request_type') !== 'existing_service'
                    || $this->input('service_type') !== 'amc'
                    || collect($this->input('amc_plan_ids', []))->map(fn ($id) => (int) $id)->all() !== [$tagging->amc_plan_id]) {
                    $validator->errors()->add('customer_amc_tagging_id', 'The AMC tagging details cannot be changed.');
                } elseif (ServiceRequest::where('customer_amc_tagging_id', $tagging->id)->where('amc_service_number', $slot)->exists()) {
                    $validator->errors()->add('amc_service_number', 'A service request already exists for this AMC service slot.');
                }
            }
        });
    }
}
