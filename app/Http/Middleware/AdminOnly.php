<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->profile !== 'admin') {
            return response()->json(['message' => 'Acesso restrito a administradores.'], 403);
        }

        return $next($request);
    }
}
