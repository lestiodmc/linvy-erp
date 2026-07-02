<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! ModuleManager::enabled($module)) {
            abort(404);
        }

        if (! $request->user()?->canAccessModule($module)) {
            abort(403);
        }

        return $next($request);
    }
}
