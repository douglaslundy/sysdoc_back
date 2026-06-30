<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlvaraRequest;
use App\Http\Requests\UpdateAlvaraRequest;
use App\Http\Resources\AlvaraResource;
use App\Models\Alvara;
use App\Services\AlvaraNumberService;
use App\Services\AlvaraPdfService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class AlvaraController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alvara::with('estabelecimento')
            ->orderByDesc('data_alvara')
            ->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('numero_alvara', 'LIKE', "%{$busca}%")
                    ->orWhereHas('estabelecimento', fn ($e) => $e->where('nome_estabelecimento', 'LIKE', "%{$busca}%")
                    );
            });
        }

        if ($request->filled('nivel_risco')) {
            $query->where('nivel_risco', $request->nivel_risco);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('estabelecimento_id')) {
            $query->where('estabelecimento_id', $request->estabelecimento_id);
        }

        if ($request->filled('data_de')) {
            $query->whereDate('data_alvara', '>=', $request->data_de);
        }

        if ($request->filled('data_ate')) {
            $query->whereDate('data_alvara', '<=', $request->data_ate);
        }

        if ($request->filled('vencimento_de')) {
            $query->whereDate('vencimento_alvara', '>=', $request->vencimento_de);
        }

        if ($request->filled('vencimento_ate')) {
            $query->whereDate('vencimento_alvara', '<=', $request->vencimento_ate);
        }

        $perPage = (int) $request->input('per_page', 15);

        return AlvaraResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $alvara = Alvara::with('estabelecimento')->find($id);

        if (! $alvara) {
            return response()->json(['error' => 'Alvará não encontrado'], 404);
        }

        AuditService::record('VIEW', $alvara, null, [
            'numero_alvara' => $alvara->numero_alvara,
            'nome_estabelecimento' => $alvara->estabelecimento?->nome_estabelecimento,
        ]);

        return response()->json(new AlvaraResource($alvara));
    }

    public function store(StoreAlvaraRequest $request): JsonResponse
    {
        $dados = $request->validated();

        try {
            $alvara = DB::transaction(function () use ($dados) {
                $dados['numero_alvara'] = AlvaraNumberService::gerar($dados['data_alvara']);

                $alvara = Alvara::create($dados);
                $alvara->load('estabelecimento');

                return $alvara;
            });
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000' && str_contains((string) $e->getMessage(), 'alvaras_numero_alvara_unique')) {
                return response()->json([
                    'message' => 'Nao foi possivel gerar um novo numero de alvara. Tente novamente.',
                ], 422);
            }

            throw $e;
        }

        return response()->json(new AlvaraResource($alvara), 201);
    }

    public function update(UpdateAlvaraRequest $request, int $id): JsonResponse
    {
        $alvara = Alvara::with('estabelecimento')->find($id);

        if (! $alvara) {
            return response()->json(['error' => 'Alvará não encontrado'], 404);
        }

        $alvara->update($request->validated());
        $alvara->load('estabelecimento');

        return response()->json(new AlvaraResource($alvara));
    }

    public function destroy(int $id): JsonResponse
    {
        $alvara = Alvara::find($id);

        if (! $alvara) {
            return response()->json(['error' => 'Alvará não encontrado'], 404);
        }

        $alvara->delete();

        return response()->json(['message' => 'Alvará excluído com sucesso']);
    }

    public function downloadPdf(int $id, AlvaraPdfService $service)
    {
        $alvara = Alvara::with('estabelecimento')->find($id);

        if (! $alvara) {
            return response()->json(['error' => 'Alvará não encontrado'], 404);
        }

        return $service->gerar($alvara);
    }
}
