<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuditService;

class LogUserAction
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        $path   = $request->path();
        $status = $response->getStatusCode();

        if ($request->isMethod('POST') && $path === 'api/login' && $status < 300) {
            AuditService::record('LOGIN');
            return;
        }

        if ($request->isMethod('POST') && $path === 'api/logout') {
            AuditService::record('LOGOUT');
        }
    }
}
