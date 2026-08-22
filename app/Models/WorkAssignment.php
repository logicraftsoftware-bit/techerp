<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkAssignment $assignment): void {
            if (! $assignment->assignment_code) {
                do {
                    $assignment->assignment_code = 'WA-'.now()->format('ymd').'-'.random_int(1000, 9999);
                } while (static::where('assignment_code', $assignment->assignment_code)->exists());
            }
        });
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class)->withTrashed();
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(WorkStatusHistory::class)->latest();
    }

    public function jobParts(): HasMany
    {
        return $this->hasMany(JobPart::class);
    }

    public function getBillTotalAttribute(): float
    {
        return (float) $this->jobParts->sum(fn (JobPart $jobPart) => $jobPart->quantity * (float) $jobPart->rate * (1 + ((float) $jobPart->tax_percent / 100)));
    }

    public function getCompletedAtAttribute()
    {
        return $this->statusHistories->firstWhere('to_status', 'completed')?->created_at
            ?? ($this->status === 'completed' ? $this->updated_at : null);
    }
}
