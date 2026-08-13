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
        return ['installation_date' => 'date', 'manufacturing_date' => 'date', 'warranty_start' => 'date', 'warranty_end' => 'date', 'amc_start' => 'date', 'amc_end' => 'date', 'buying_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    protected static function booted(): void
    {
        static::creating(function (Machine $machine): void {
            if ($machine->machine_code) {
                return;
            }
            $letters = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $machine->machine_name), 0, 2));
            $prefix = str_pad($letters, 2, 'X');
            do {
                $machine->machine_code = $prefix.random_int(100000, 999999);
            } while (static::withTrashed()->where('machine_code', $machine->machine_code)->exists());
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function brandMaster(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function machineCategory(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MachineDocument::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(MachineStockTransaction::class);
    }
}
