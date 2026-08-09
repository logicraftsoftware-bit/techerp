<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'joining_date' => 'date', 'monthly_salary' => 'decimal:2', 'daily_salary' => 'decimal:2', 'hourly_rate' => 'decimal:2', 'overtime_rate' => 'decimal:2'];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot(['skill_level', 'experience_years', 'certification', 'certification_expiry', 'remarks'])->withTimestamps();
    }
}
