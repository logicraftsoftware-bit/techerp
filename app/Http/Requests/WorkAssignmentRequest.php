<?php

namespace App\Http\Requests;

use App\Models\WorkAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_request_id' => ['required', 'exists:service_requests,id'],
            'technician_id' => ['required', 'exists:technicians,id'],
            'assignment_role' => ['required', Rule::in(['primary', 'support', 'inspection'])],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'service_address' => ['required', 'string'],
            'work_instructions' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $currentId = $this->route('assignment')?->id;
            $conflict = WorkAssignment::where('technician_id', $this->integer('technician_id'))
                ->whereDate('scheduled_date', $this->input('scheduled_date'))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->when($currentId, fn ($query) => $query->whereKeyNot($currentId))
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
                ->exists();
            if ($conflict) {
                $validator->errors()->add('technician_id', 'This technician already has an overlapping assignment.');
            }
        });
    }
}
