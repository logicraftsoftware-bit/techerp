<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAmcTagging extends Model
{
    protected $fillable = ['customer_id', 'machine_id', 'amc_plan_id', 'start_date', 'end_date', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public static function calculateEndDate(CarbonInterface $startDate, string $duration): CarbonInterface
    {
        $years = match ($duration) {
            '1_year' => 1,
            '2_years' => 2,
            '3_years' => 3,
            default => throw new \InvalidArgumentException('Unsupported AMC duration.'),
        };

        return $startDate->copy()->addYearsNoOverflow($years)->subDay();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function amcPlan(): BelongsTo
    {
        return $this->belongsTo(AmcPlan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
