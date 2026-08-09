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
            'employee_code' => ['required', 'max:50', Rule::unique('technicians')->ignore($id)], 'name' => ['required', 'max:150'], 'profile_photo' => ['nullable', 'image', 'max:4096'], 'gender' => ['nullable', Rule::in(['male', 'female', 'other'])], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'mobile' => ['required', 'max:20', Rule::unique('technicians')->ignore($id)], 'email' => ['nullable', 'email', Rule::unique('technicians')->ignore($id)], 'emergency_contact' => ['nullable', 'max:20'], 'address' => ['nullable'], 'city' => ['nullable', 'max:100'], 'state' => ['nullable', 'max:100'], 'pin_code' => ['nullable', 'max:10'],
            'joining_date' => ['required', 'date'], 'department' => ['nullable', 'max:100'], 'designation' => ['nullable', 'max:100'], 'technician_type' => ['nullable', 'max:100'], 'reporting_manager_id' => ['nullable', 'exists:technicians,id', Rule::notIn([$id])], 'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract'])], 'status' => ['required', Rule::in(['active', 'inactive', 'terminated'])],
            'salary_type' => ['required', Rule::in(['monthly', 'daily', 'hourly'])], 'monthly_salary' => ['nullable', 'numeric', 'min:0'], 'daily_salary' => ['nullable', 'numeric', 'min:0'], 'hourly_rate' => ['nullable', 'numeric', 'min:0'], 'overtime_rate' => ['nullable', 'numeric', 'min:0'], 'travel_allowance' => ['nullable', 'numeric', 'min:0'], 'food_allowance' => ['nullable', 'numeric', 'min:0'], 'other_allowance' => ['nullable', 'numeric', 'min:0'], 'pf' => ['nullable', 'numeric', 'min:0'], 'esi' => ['nullable', 'numeric', 'min:0'], 'advance' => ['nullable', 'numeric', 'min:0'], 'other_deduction' => ['nullable', 'numeric', 'min:0'], 'skills' => ['nullable', 'array'], 'skills.*' => ['exists:skills,id'],
        ];
    }
}
