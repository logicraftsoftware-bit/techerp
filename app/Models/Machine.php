<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['installation_date' => 'date', 'warranty_start' => 'date', 'warranty_end' => 'date', 'amc_start' => 'date', 'amc_end' => 'date', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MachineDocument::class);
    }
}
