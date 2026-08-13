<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_system', 'is_active'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Role $role): void {
            if ($role->slug) {
                return;
            }
            $base = Str::slug($role->name);
            $slug = $base;
            $suffix = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $base.'-'.++$suffix;
            }
            $role->slug = $slug;
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
