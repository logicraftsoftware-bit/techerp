<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $request): void {
            if ($request->request_code) {
                return;
            }

            do {
                $request->request_code = 'SR-'.now()->format('ymd').'-'.random_int(1000, 9999);
            } while (static::where('request_code', $request->request_code)->exists());
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class)->withTrashed();
    }

    public function machineCategory(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amcPlans(): BelongsToMany
    {
        return $this->belongsToMany(AmcPlan::class)->withTimestamps();
    }

    public function workAssignments(): HasMany
    {
        return $this->hasMany(WorkAssignment::class);
    }
}
