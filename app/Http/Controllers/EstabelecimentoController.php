<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstabelecimentoRequest;
use App\Http\Requests\UpdateEstabelecimentoRequest;
use App\Http\Resources\EstabelecimentoResource;
use App\Models\Estabelecimento;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EstabelecimentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Estabelecimento::withCount('alvaras')
            ->orderBy('nome_estabelecimento');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome_estabelecimento', 'LIKE', "%{$busca}%")
                    ->orWhere('nome_responsavel', 'LIKE', "%{$busca}%")
                    ->orWhere('cnaes', 'LIKE', "%{$busca}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);

        return EstabelecimentoResource::collection(
            $query->paginate($perPage)
        );
    }

    public function select(): JsonResponse
    {
        $estabelecimentos = Estabelecimento::select('id', 'nome_estabelecimento', 'nome_responsavel', 'endereco')
            ->orderBy('nome_estabelecimento')
            ->get();

        return response()->json($estabelecimentos);
    }

    public function show(int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::withCount('alvaras')->find($id);

        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        AuditService::record('VIEW', $estabelecimento, null, [
            'nome_estabelecimento' => $estabelecimento->nome_estabelecimento,
        ]);

        return response()->json(new EstabelecimentoResource($estabelecimento));
    }

    public function store(StoreEstabelecimentoRequest $request): JsonResponse
    {
        $estabelecimento = Estabelecimento::create($request->validated());
        AuditService::record('CREATE', $estabelecimento, null, $estabelecimento->toArray());

        return response()->json(new EstabelecimentoResource($estabelecimento), 201);
    }

    public function update(UpdateEstabelecimentoRequest $request, int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::find($id);

        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        $old = $estabelecimento->toArray();
        $estabelecimento->update($request->validated());
        AuditService::record('UPDATE', $estabelecimento, $old, $estabelecimento->toArray());

        return response()->json(new EstabelecimentoResource($estabelecimento));
    }

    public function destroy(int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::find($id);

        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        AuditService::record('DELETE', $estabelecimento, $estabelecimento->toArray(), null);
        $estabelecimento->delete();

        return response()->json(['message' => 'Estabelecimento excluído com sucesso']);
    }
}
