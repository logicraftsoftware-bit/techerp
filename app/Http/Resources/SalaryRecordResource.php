<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'salary_code' => $this->salary_code,
            'salary_month' => $this->salary_month->format('Y-m'),
            'working_days' => $this->working_days,
            'present_days' => (float) $this->present_days,
            'paid_leave_days' => (float) $this->paid_leave_days,
            'overtime_hours' => (float) $this->overtime_hours,
            'basic_salary' => (float) $this->basic_salary,
            'allowances' => (float) $this->allowances,
            'overtime_amount' => (float) $this->overtime_amount,
            'deductions' => (float) $this->deductions,
            'net_salary' => (float) $this->net_salary,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'payment_reference' => $this->payment_reference,
        ];
    }
}
