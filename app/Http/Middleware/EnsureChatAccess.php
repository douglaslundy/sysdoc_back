<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureChatAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->canUseChat()) {
            return response()->json(['message' => 'Usuário sem permissão para acessar o chat.'], 403);
        }

        return $next($request);
    }
}
