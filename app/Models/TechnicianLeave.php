<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianLeave extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['from_date' => 'date', 'to_date' => 'date', 'actioned_at' => 'datetime'];
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
