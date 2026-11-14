<?php

namespace App\Http\Middleware;

use App\Services\Secretarias\ActiveSecretaria;
use Closure;
use Illuminate\Http\Request;

/** Marca o início da requisição HTTP: sem secretaria resolvida, models isolados falham fechado. */
class BeginSecretariaContext
{
    public function __construct(private ActiveSecretaria $active)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->active->beginRequest();

        return $next($request);
    }
}
