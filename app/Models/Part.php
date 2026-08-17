<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Part extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'has_amc' => 'boolean', 'has_warranty' => 'boolean'];
    }

    public function machineCategory(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class);
    }

    public function unitMaster(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function brandMaster(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
