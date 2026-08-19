<?php

namespace App\Http\Middleware;

use App\Services\MissedCheckInMarker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RunMissedCheckInMarker
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'missed-checkin-marker-ran:'.now()->format('Y-m-d-H-i');

        if (Cache::add($key, true, 90)) {
            app(MissedCheckInMarker::class)->run();
        }

        return $next($request);
    }
}
