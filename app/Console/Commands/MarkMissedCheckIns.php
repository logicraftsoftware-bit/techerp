<?php

namespace App\Console\Commands;

use App\Services\MissedCheckInMarker;
use Illuminate\Console\Command;

class MarkMissedCheckIns extends Command
{
    protected $signature = 'attendance:mark-missed-checkins';

    protected $description = 'Auto-mark leave/absent for staff who missed their check-in grace window';

    public function handle(MissedCheckInMarker $marker): int
    {
        $marker->run();

        return self::SUCCESS;
    }
}
