<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->is_active) {
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }

        if (!empty($roles) && !in_array($request->user()->role, $roles)) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        return $next($request);
    }
}
