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
                Rule::exists('machines', 'id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
            'amc_plan_id' => ['required', 'integer', Rule::exists('amc_plans', 'id')->where('status', 'active')],
            'service_count' => ['required', 'integer', 'min:0', 'max:999'],
            'payment_collected_by' => ['required', Rule::in(['staff', 'technician'])],
            'paid_amount' => [Rule::requiredIf($this->input('payment_collected_by') === 'staff'), 'nullable', 'numeric', 'min:0'],
            'payment_method' => [Rule::requiredIf($this->input('payment_collected_by') === 'staff'), 'nullable', Rule::in(['cash', 'upi', 'card', 'bank_transfer', 'cheque'])],
            'payment_remarks' => [Rule::requiredIf($this->input('payment_collected_by') === 'staff'), 'nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
        ];
    }
}
