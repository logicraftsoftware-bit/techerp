<?php

namespace App\Http\Middleware;

use App\Services\MissedCheckInMarker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RunMissedCheckInMarker
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'missed-checkin-marker-ran:'.now()->format('Y-m-d-H-i');

        if (Cache::add($key, true, 90)) {
            try {
                app(MissedCheckInMarker::class)->run();
            } catch (Throwable $e) {
                Log::error('MissedCheckInMarker failed: '.$e->getMessage());
            }
        }

        return $next($request);
    }
}
