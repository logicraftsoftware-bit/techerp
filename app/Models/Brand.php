<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['brand_name'];

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}
