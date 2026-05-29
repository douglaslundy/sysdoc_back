<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEquipeAps
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_rt_psf || $user->rt_all_teams) {
            $request->attributes->set('_ines_permitidos', null);
            return $next($request);
        }

        $ines = $user->equipeAps->pluck('nu_ine')->toArray();
        $request->attributes->set('_ines_permitidos', $ines);

        return $next($request);
    }
}
