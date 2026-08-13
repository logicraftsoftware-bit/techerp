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

            'salary_type' => ['required', Rule::in(['monthly', 'daily'])],
            'monthly_salary' => ['nullable', 'required_if:salary_type,monthly', 'numeric', 'min:0'],
            'daily_salary' => ['nullable', 'required_if:salary_type,daily', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'travel_allowance' => ['nullable', 'numeric', 'min:0'],
            'food_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf' => ['nullable', 'numeric', 'min:0'],
            'esi' => ['nullable', 'numeric', 'min:0'],
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
        });
    }
}
