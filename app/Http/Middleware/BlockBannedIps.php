<?php

namespace App\Http\Middleware;

use App\Services\BannedIpService;
use Closure;
use Illuminate\Http\Request;

class BlockBannedIps
{
    public function __construct(private BannedIpService $bans)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Quem já envia token passa (a autenticação decide): assim um admin
        // banido por engano ainda alcança a tela de desbanir.
        if (! $request->bearerToken() && $this->bans->isBanned($request->ip())) {
            return response()->json(['message' => 'Acesso bloqueado.'], 403);
        }

        return $next($request);
    }
}
