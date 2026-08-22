<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerAmcTaggingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'machine_id' => [
                'required',
                'integer',
                Rule::exists('machines', 'id')->where(fn ($query) => $query->where('customer_id', $this->input('customer_id'))->whereNull('deleted_at')),
            ],
            'amc_plan_id' => ['required', 'integer', Rule::exists('amc_plans', 'id')->where('status', 'active')],
            'start_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['machine_id.exists' => 'The selected machine does not belong to the selected customer.'];
    }
}
