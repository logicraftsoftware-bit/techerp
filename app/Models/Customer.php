<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if ($customer->customer_code) {
                return;
            }

            $letters = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $customer->customer_name), 0, 2));
            $prefix = str_pad($letters, 2, 'X');

            do {
                $customer->customer_code = $prefix.random_int(100000, 999999);
            } while (static::withTrashed()->where('customer_code', $customer->customer_code)->exists());
        });
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'referred_by_technician_id');
    }
}
