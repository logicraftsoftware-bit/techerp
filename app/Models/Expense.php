<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expense_date' => 'date'];
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignment()
    {
        return $this->belongsTo(WorkAssignment::class, 'work_assignment_id');
    }
}
