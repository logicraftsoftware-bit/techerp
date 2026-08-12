<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmcPlan extends Model
{
    protected $fillable = [
        'plan_name',
        'machine_category_id',
        'brand_id',
        'plan_type',
        'description',
        'duration',
        'parts_included',
        'price',
        'tax_percent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'parts_included' => 'boolean',
            'price' => 'decimal:2',
            'tax_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AmcPlan $plan): void {
            if ($plan->plan_code) {
                return;
            }
            $next = static::count() + 1;
            do {
                $plan->plan_code = 'AMC-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                $next++;
            } while (static::where('plan_code', $plan->plan_code)->exists());
        });
    }

    public function machineCategory(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class);
    }

    public function brandMaster(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
