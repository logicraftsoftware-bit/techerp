<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('technician')?->id;

        return [
            'name' => ['required', 'max:150'], 'profile_photo' => ['nullable', 'file', 'mimetypes:image/*', 'max:4096'], 'gender' => ['required', Rule::in(['male', 'female', 'other'])], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'mobile' => ['required', 'max:20', Rule::unique('technicians')->ignore($id)], 'email' => ['nullable', 'email', Rule::unique('technicians')->ignore($id)], 'emergency_contact' => ['nullable', 'max:20'], 'address' => ['nullable'], 'city' => ['nullable', 'max:100'], 'state' => ['nullable', 'max:100'], 'pin_code' => ['nullable', 'max:10'], 'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'joining_date' => ['required', 'date'], 'department_id' => ['nullable', 'exists:departments,id'], 'designation' => ['nullable', 'max:100'], 'reporting_manager' => ['nullable', 'string', 'regex:/^(technician|user):\d+$/'], 'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract'])], 'status' => ['required', Rule::in(['active', 'inactive', 'terminated'])],
            'salary_structure_type' => ['required', Rule::in(['fixed', 'commission_based'])],
            'salary_type' => ['nullable', Rule::in(['monthly', 'daily'])], 'monthly_salary' => ['nullable', 'numeric', 'min:0'], 'daily_salary' => ['nullable', 'numeric', 'min:0'], 'overtime_rate' => ['nullable', 'numeric', 'min:0'], 'travel_allowance' => ['nullable', 'numeric', 'min:0'], 'food_allowance' => ['nullable', 'numeric', 'min:0'], 'other_allowance' => ['nullable', 'numeric', 'min:0'], 'pf' => ['nullable', 'numeric', 'min:0'], 'esi' => ['nullable', 'numeric', 'min:0'], 'monthly_paid_leave_days' => ['nullable', 'integer', 'min:0'], 'skills' => ['nullable', 'array'], 'skills.*' => ['exists:skills,id'], 'commission_type_ids' => ['nullable', 'array'], 'commission_type_ids.*' => ['integer', 'exists:commission_types,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('salary_structure_type') === 'fixed') {
                if (! $this->filled('salary_type')) {
                    $validator->errors()->add('salary_type', 'Salary type is required for a fixed salary structure.');
                } elseif ($this->input('salary_type') === 'monthly' && ! $this->filled('monthly_salary')) {
                    $validator->errors()->add('monthly_salary', 'Monthly salary is required.');
                } elseif ($this->input('salary_type') === 'daily' && ! $this->filled('daily_salary')) {
                    $validator->errors()->add('daily_salary', 'Daily salary is required.');
                }
            } elseif ($this->input('salary_structure_type') === 'commission_based' && empty($this->input('commission_type_ids'))) {
                $validator->errors()->add('commission_type_ids', 'Select at least one commission type for a commission-based salary structure.');
            }
        });
    }
}
