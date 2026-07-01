<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlvaraRequest;
use App\Http\Requests\UpdateAlvaraRequest;
use App\Http\Resources\AlvaraResource;
use App\Models\Alvara;
use App\Services\AlvaraNumberService;
use App\Services\AlvaraPdfService;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        if (empty($dados['vencimento_alvara'])) {
            $dados['vencimento_alvara'] = self::vencimentoPadrao($dados['data_alvara']);
        }

        $maxTentativas = 3;

        for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
            try {
                $alvara = DB::transaction(function () use ($dados) {
                    $dados['numero_alvara'] = AlvaraNumberService::gerar($dados['data_alvara']);

                    $alvara = Alvara::create($dados);
                    $alvara->load('estabelecimento');

                    return $alvara;
                });

                return response()->json(new AlvaraResource($alvara), 201);
            } catch (QueryException $e) {
                $numeroAlvaraDuplicado = (string) $e->getCode() === '23000'
                    && str_contains((string) $e->getMessage(), 'alvaras_numero_alvara_unique');

                if (! $numeroAlvaraDuplicado) {
                    throw $e;
                }

                $contexto = [
                    'tentativa' => $tentativa,
                    'data_alvara' => $dados['data_alvara'],
                    'exception' => $e->getMessage(),
                ];

                if ($tentativa < $maxTentativas) {
                    Log::warning('Colisao ao gerar numero de alvara, tentando novamente.', $contexto);

                    continue;
                }

                Log::error('Nao foi possivel gerar numero de alvara apos multiplas tentativas.', $contexto);

                return response()->json([
                    'message' => 'Nao foi possivel gerar um novo numero de alvara. Tente novamente.',
                ], 422);
            }
        }
    }

    public function update(UpdateAlvaraRequest $request, int $id): JsonResponse
    {
        $alvara = Alvara::with('estabelecimento')->find($id);

        if (! $alvara) {
            return response()->json(['error' => 'Alvará não encontrado'], 404);
        }

        $dados = $request->validated();

        if (array_key_exists('vencimento_alvara', $dados) && empty($dados['vencimento_alvara'])) {
            $dados['vencimento_alvara'] = self::vencimentoPadrao($dados['data_alvara'] ?? $alvara->data_alvara);
        }

        $alvara->update($dados);
        $alvara->load('estabelecimento');

        return response()->json(new AlvaraResource($alvara));
    }

    private static function vencimentoPadrao(string $dataAlvara): string
    {
        return Carbon::parse($dataAlvara)->addDays(365)->toDateString();
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
