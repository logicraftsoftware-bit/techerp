<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommissionType extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2'];
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'commission_type_technician')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'commission_type_user')->withTimestamps();
    }
}
