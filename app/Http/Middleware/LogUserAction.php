<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;

class LogUserAction
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        $path = $request->path();

        // LOGIN é auditado diretamente no AuthController (Auth::user() é null aqui)
        if ($request->isMethod('POST') && $path === 'api/logout') {
            AuditService::record('LOGOUT');
        }
    }
}
