<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $technician = $request->user();

        if ($technician && $technician->status !== 'active') {
            $technician->tokens()->delete();

            abort(401, 'Your account has been deactivated.');
        }

        return $next($request);
    }
}
