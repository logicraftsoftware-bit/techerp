<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'gender' => $this->gender,
            'designation' => $this->designation,
            'department' => $this->departmentMaster?->department_name,
            'status' => $this->status,
            'joining_date' => $this->joining_date?->format('Y-m-d'),
            'monthly_paid_leave_days' => $this->monthly_paid_leave_days,
            'expected_check_in_time' => $this->expected_check_in_time,
            'expected_check_out_time' => $this->expected_check_out_time,
        ];
    }
}
