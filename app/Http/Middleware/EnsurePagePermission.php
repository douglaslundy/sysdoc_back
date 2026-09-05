<?php

namespace App\Http\Middleware;

use App\Services\Authorization\PagePermissionService;
use Closure;
use Illuminate\Http\Request;

class EnsurePagePermission
{
    public function handle(Request $request, Closure $next, string ...$paths)
    {
        $user = $request->user();

        if (! $user || ! app(PagePermissionService::class)->canAccessAny($user, $paths)) {
            return response()->json(['message' => 'Usuário sem permissão para esta página.'], 403);
        }

        return $next($request);
    }
}
