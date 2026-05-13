<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageViewAuditController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ], [
            'path.required' => 'O caminho da pÃ¡gina Ã© obrigatÃ³rio.',
            'path.string' => 'O caminho da pÃ¡gina deve ser um texto.',
            'path.max' => 'O caminho da pÃ¡gina deve ter no mÃ¡ximo 255 caracteres.',
        ]);

        AuditService::record('VIEW', null, null, [
            'event' => 'PAGE_VIEW',
            'path' => $data['path'],
        ]);

        return response()->json(['ok' => true]);
    }
}
