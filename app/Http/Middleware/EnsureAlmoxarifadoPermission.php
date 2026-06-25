<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAlmoxarifadoPermission
{
    public function handle(Request $request, Closure $next, string $action)
    {
        $user = $request->user();
        if (! $user || ! $user->canUseAlmoxarifadoAction($action)) {
            return response()->json(['message' => 'Usuário sem permissão para esta ação no almoxarifado.'], 403);
        }

        return $next($request);
    }
}
