<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_date' => $this->attendance_date->format('Y-m-d'),
            'status' => $this->attendance_status,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'overtime_hours' => (float) $this->overtime_hours,
            'remarks' => $this->remarks,
        ];
    }
}
