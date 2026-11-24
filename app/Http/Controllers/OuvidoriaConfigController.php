<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOuvidoriaConfigRequest;
use App\Http\Requests\UploadOuvidoriaLogoRequest;
use App\Models\OuvidoriaConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/** Textos e logo do portal público da ouvidoria: leitura pública, gravação só do admin (rotas em middleware admin). */
class OuvidoriaConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(OuvidoriaConfig::atual()->paraPortal());
    }

    public function update(UpdateOuvidoriaConfigRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $config = OuvidoriaConfig::atual();
        $config->update([
            'nome_marca' => $dados['nome_marca'],
            'registrar_titulo' => $dados['registrar']['titulo'],
            'registrar_texto' => $dados['registrar']['texto'] ?? null,
            'registrar_itens' => $dados['registrar']['itens'],
            'consultar_titulo' => $dados['consultar']['titulo'],
            'consultar_texto' => $dados['consultar']['texto'] ?? null,
            'consultar_itens' => $dados['consultar']['itens'],
        ]);

        return response()->json($config->paraPortal());
    }

    public function enviarLogo(UploadOuvidoriaLogoRequest $request): JsonResponse
    {
        $config = OuvidoriaConfig::atual();
        $anterior = $config->logo_path;
        $config->update(['logo_path' => $request->file('logo')->store('ouvidoria', 'public')]);
        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return response()->json($config->paraPortal());
    }

    public function removerLogo(): JsonResponse
    {
        $config = OuvidoriaConfig::atual();
        if ($config->logo_path) {
            Storage::disk('public')->delete($config->logo_path);
            $config->update(['logo_path' => null]);
        }

        return response()->json($config->paraPortal());
    }
}
