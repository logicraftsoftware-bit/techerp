<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
