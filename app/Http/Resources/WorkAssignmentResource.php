<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $serviceRequest = $this->serviceRequest;

        return [
            'id' => $this->id,
            'assignment_code' => $this->assignment_code,
            'assignment_role' => $this->assignment_role,
            'scheduled_date' => $this->scheduled_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'priority' => $this->priority,
            'status' => $this->status,
            'service_address' => $this->service_address,
            'work_instructions' => $this->work_instructions,
            'internal_notes' => $this->internal_notes,
            'service_request' => [
                'id' => $serviceRequest->id,
                'request_code' => $serviceRequest->request_code,
                'subject' => $serviceRequest->subject,
                'complaint' => $serviceRequest->complaint,
                'service_type' => $serviceRequest->service_type,
                'customer_name' => $serviceRequest->customer?->customer_name,
                'contact_phone' => $serviceRequest->contact_phone,
                'product_name' => $serviceRequest->machine?->machine_name ?? $serviceRequest->product_name,
                'model' => $serviceRequest->model,
                'serial_number' => $serviceRequest->serial_number,
                'asset_number' => $serviceRequest->asset_number,
                'city' => $serviceRequest->city,
                'state' => $serviceRequest->state,
                'pin_code' => $serviceRequest->pin_code,
            ],
        ];
    }
}
