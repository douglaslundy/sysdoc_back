<?php

namespace App\Http\Middleware;

use App\Models\Secretaria;
use App\Services\Secretarias\ActiveSecretaria;
use Closure;
use Illuminate\Http\Request;

/** Resolve a secretaria ativa pelo header X-Secretaria-Id (id, ou "all" só para admin). */
class ResolveActiveSecretaria
{
    public function __construct(private ActiveSecretaria $active)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $isAdmin = $user->profile === 'admin';
        // Admin enxerga todas as ativas; os demais, só as vinculadas e ativas (primeira por id = padrão).
        $ids = ($isAdmin ? Secretaria::query() : $user->secretarias())
            ->where('secretarias.ativa', true)->orderBy('secretarias.id')->pluck('secretarias.id')
            ->map(fn ($id) => (int) $id)->all();

        $header = $request->header('X-Secretaria-Id');

        if ($header === null || $header === '') {
            if ($isAdmin) {
                $this->active->set(null, true, $ids);
            } else {
                $this->active->set($ids[0] ?? null, false, $ids ? [$ids[0]] : []);
            }

            return $next($request);
        }

        if ($header === 'all') {
            if (! $isAdmin) {
                return response()->json(['message' => 'Secretaria não permitida.'], 403);
            }
            $this->active->set(null, true, $ids);

            return $next($request);
        }

        if (! ctype_digit($header) || ! in_array((int) $header, $ids, true)) {
            return response()->json(['message' => 'Secretaria não vinculada ao usuário.'], 403);
        }

        $this->active->set((int) $header, false, [(int) $header]);

        return $next($request);
    }
}
