<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePainelPublicoRequest;
use App\Models\PainelPublicoConfig;

class PainelPublicoController extends Controller
{
    /** Leitura pública (sem login) e gravação só do admin; mesma resposta nos dois casos. */
    public function show()
    {
        return response()->json(PainelPublicoConfig::atual()->only(['pagina_url', 'esquerda_url', 'direita_url', 'largura_esquerda']));
    }

    public function update(UpdatePainelPublicoRequest $request)
    {
        $config = PainelPublicoConfig::atual();
        $config->update($request->validated());

        return response()->json($config->only(['pagina_url', 'esquerda_url', 'direita_url', 'largura_esquerda']));
    }
}
