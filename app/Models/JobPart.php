<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPart extends Model
{
    protected $guarded = ['id'];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function assignment()
    {
        return $this->belongsTo(WorkAssignment::class, 'work_assignment_id');
    }
}
