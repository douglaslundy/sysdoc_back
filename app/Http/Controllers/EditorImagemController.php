<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadEditorImagemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Imagens do editor de texto rico. Ficam no disco público com nome aleatório: a tag <img> não envia o login
 * e a resposta da ouvidoria é lida sem login.
 * shortcut: imagem removida do texto continua no disco; limpar órfãs se o volume crescer.
 */
class EditorImagemController extends Controller
{
    public function store(UploadEditorImagemRequest $request): JsonResponse
    {
        $caminho = $request->file('imagem')->store('editor', 'public');

        return response()->json(['url' => Storage::disk('public')->url($caminho)]);
    }
}
