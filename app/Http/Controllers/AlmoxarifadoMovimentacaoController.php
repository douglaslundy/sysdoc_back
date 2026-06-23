<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoMovimentacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmoxarifadoMovimentacaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AlmoxarifadoMovimentacao::query()
            ->with([
                'produto:id,nome,codigo_interno',
                'secretariaOrigem:id,nome',
                'secretariaDestino:id,nome',
                'user:id,name',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('produto', function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo_interno', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $page = max(1, (int) $request->input('page', 1));

        return response()->json($query->paginate($perPage, ['*'], 'page', $page));
    }
}
