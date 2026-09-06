<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalizacaoRequest;
use App\Http\Requests\UpdateFiscalizacaoRequest;
use App\Http\Resources\FiscalizacaoResource;
use App\Models\Fiscalizacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalizacaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Fiscalizacao::with(['estabelecimento', 'fiscal'])
            ->orderByDesc('data_visita')
            ->orderByDesc('id');

        if ($request->filled('estabelecimento_id')) {
            $query->where('estabelecimento_id', $request->estabelecimento_id);
        }

        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->whereHas('estabelecimento', fn ($e) => $e->where('nome_estabelecimento', 'LIKE', "%{$busca}%"));
        }

        $perPage = (int) $request->input('per_page', 15);

        return FiscalizacaoResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $fiscalizacao = Fiscalizacao::with(['estabelecimento', 'fiscal', 'attachments'])->find($id);

        if (! $fiscalizacao) {
            return response()->json(['error' => 'Fiscalização não encontrada'], 404);
        }

        return response()->json(new FiscalizacaoResource($fiscalizacao));
    }

    public function store(StoreFiscalizacaoRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $dados['fiscal_id'] = $request->user()->id;

        $fiscalizacao = Fiscalizacao::create($dados);
        $fiscalizacao->load(['estabelecimento', 'fiscal']);

        return response()->json(new FiscalizacaoResource($fiscalizacao), 201);
    }

    public function update(UpdateFiscalizacaoRequest $request, int $id): JsonResponse
    {
        $fiscalizacao = Fiscalizacao::find($id);

        if (! $fiscalizacao) {
            return response()->json(['error' => 'Fiscalização não encontrada'], 404);
        }

        $fiscalizacao->update($request->validated());
        $fiscalizacao->load(['estabelecimento', 'fiscal']);

        return response()->json(new FiscalizacaoResource($fiscalizacao));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! app(\App\Services\Authorization\PagePermissionService::class)->canAccess($user, '/fiscalizacoes')) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $fiscalizacao = Fiscalizacao::find($id);

        if (! $fiscalizacao) {
            return response()->json(['error' => 'Fiscalização não encontrada'], 404);
        }

        $fiscalizacao->delete();

        return response()->json(['message' => 'Fiscalização excluída com sucesso']);
    }
}
