<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('user') ? 'update' : 'create', $this->route('user') ?? User::class) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],

            'avatar' => ['nullable', 'file', 'mimetypes:image/*', 'max:4096'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'emergency_contact' => ['nullable', 'max:20'],
            'address' => ['nullable'],
            'city' => ['nullable', 'max:100'],
            'state' => ['nullable', 'max:100'],
            'pin_code' => ['nullable', 'max:10'],

            'joining_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation' => ['nullable', 'max:100'],
            'reporting_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract'])],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'terminated'])],

            'salary_structure_type' => ['required', Rule::in(['fixed', 'commission_based'])],
            'salary_type' => ['nullable', Rule::in(['monthly', 'daily'])],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'travel_allowance' => ['nullable', 'numeric', 'min:0'],
            'food_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf' => ['nullable', 'numeric', 'min:0'],
            'esi' => ['nullable', 'numeric', 'min:0'],
            'monthly_paid_leave_days' => ['nullable', 'integer', 'min:0'],
            'commission_type_ids' => ['nullable', 'array'],
            'commission_type_ids.*' => ['integer', 'exists:commission_types,id'],
            'expected_check_in_time' => ['nullable', 'date_format:H:i'],
            'expected_check_out_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $superAdminId = Role::where('slug', 'super-admin')->value('id');
            if ($superAdminId && in_array($superAdminId, $this->input('roles', []))) {
                $validator->errors()->add('roles', 'Super Admin cannot be assigned from the CRM.');
            }

            $userId = $this->route('user')?->id;
            if ($userId && (int) $this->input('reporting_manager_id') === $userId) {
                $validator->errors()->add('reporting_manager_id', 'A user cannot report to themselves.');
            }

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
