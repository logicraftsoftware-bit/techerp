<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->employee_code) {
                return;
            }

            $prefix = str($user->name)->upper()->replaceMatches('/[^A-Z]/', '')->substr(0, 2)->padRight(2, 'X');
            do {
                $code = $prefix.random_int(100000, 999999);
            } while (self::withTrashed()->where('employee_code', $code)->exists());

            $user->employee_code = $code;
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function departmentMaster(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->roles->contains(fn (Role $role) => in_array($role->slug, $roles, true));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasRole('super-admin') || $this->permissions->contains('slug', $permission);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'monthly_salary' => 'decimal:2',
            'daily_salary' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'travel_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'pf' => 'decimal:2',
            'esi' => 'decimal:2',
        ];
    }
}
