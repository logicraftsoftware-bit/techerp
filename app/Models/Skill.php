<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class)->withPivot(['skill_level', 'experience_years', 'certification', 'certification_expiry', 'remarks'])->withTimestamps();
    }
}
