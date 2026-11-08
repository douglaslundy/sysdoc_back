<?php

namespace App\Http\Middleware;

use App\Models\Secretaria;
use App\Services\Secretarias\ActiveSecretaria;
use Closure;
use Illuminate\Http\Request;

/** Restringe a rota a uma secretaria (por slug). Admin em "Todas" passa; sem secretaria ativa, falha fechado. */
class OnlySecretaria
{
    public function __construct(private ActiveSecretaria $active)
    {
    }

    public function handle(Request $request, Closure $next, string $slug)
    {
        if ($this->active->isAll() && $request->user()?->profile === 'admin') {
            return $next($request);
        }

        $id = $this->active->id();
        if ($id && $id === (int) Secretaria::where('slug', $slug)->value('id')) {
            return $next($request);
        }

        return response()->json(['message' => 'Recurso disponível apenas para a secretaria de Saúde.'], 403);
    }
}
