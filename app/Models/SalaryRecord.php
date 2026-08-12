<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['salary_month' => 'date', 'paid_at' => 'date'];
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
