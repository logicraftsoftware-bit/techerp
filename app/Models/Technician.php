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

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(function (Technician $technician) {
            if ($technician->employee_code) {
                return;
            }

            $prefix = str($technician->name)->upper()->replaceMatches('/[^A-Z]/', '')->substr(0, 2)->padRight(2, 'X');
            do {
                $code = $prefix.random_int(100000, 999999);
            } while (self::withTrashed()->where('employee_code', $code)->exists());

            $technician->employee_code = $code;
        });
    }

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'joining_date' => 'date', 'monthly_salary' => 'decimal:2', 'daily_salary' => 'decimal:2', 'hourly_rate' => 'decimal:2', 'overtime_rate' => 'decimal:2'];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function departmentMaster(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reportingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_user_id');
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
