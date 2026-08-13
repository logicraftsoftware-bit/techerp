<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $slug, string $action): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super-admin') || $user->hasPermission("$slug.$action")), 403);

        return $next($request);
    }
}
