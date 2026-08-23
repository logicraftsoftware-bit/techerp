<?php

namespace App\Notifications;

use App\Models\WorkAssignment;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

class JobAssigned extends Notification
{
    public function __construct(private readonly WorkAssignment $assignment) {}

    public function via(mixed $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'job_assigned',
            'work_assignment_id' => $this->assignment->id,
            'assignment_code' => $this->assignment->assignment_code,
            'subject' => $this->assignment->serviceRequest->subject,
            'scheduled_date' => $this->assignment->scheduled_date->format('Y-m-d'),
        ];
    }

    public function toFcm(mixed $notifiable): array
    {
        return [
            'title' => 'New Job Assigned',
            'body' => $this->assignment->serviceRequest->subject.' - '.$this->assignment->scheduled_date->format('d M Y'),
            'data' => [
                'type' => 'job_assigned',
                'work_assignment_id' => (string) $this->assignment->id,
            ],
        ];
    }
}
