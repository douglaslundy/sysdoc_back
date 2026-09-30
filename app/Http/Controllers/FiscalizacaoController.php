<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalizacaoRequest;
use App\Http\Requests\UpdateFiscalizacaoRequest;
use App\Http\Resources\FiscalizacaoResource;
use App\Models\Fiscalizacao;
use App\Services\Fiscalizacao\FiscalizacaoProtocolo;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use App\Services\Fiscalizacao\VigilanciaAvisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalizacaoController extends Controller
{
    public function __construct(private FiscalizacaoTimeline $timeline, private VigilanciaAvisoService $aviso)
    {
    }

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

        if ($request->filled('origem')) {
            $query->where('origem', $request->origem);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('protocolo', 'LIKE', "%{$busca}%")
                    ->orWhere('estabelecimento_nome_informado', 'LIKE', "%{$busca}%")
                    ->orWhereHas('estabelecimento', fn ($e) => $e->where('nome_estabelecimento', 'LIKE', "%{$busca}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 15);

        return FiscalizacaoResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $fiscalizacao = Fiscalizacao::with(['estabelecimento', 'fiscal'])->find($id);

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
        $fiscalizacao->forceFill([
            'protocolo' => FiscalizacaoProtocolo::for($fiscalizacao->id, $fiscalizacao->created_at),
        ])->saveQuietly();
        $fiscalizacao->refresh(); // traz os padrões do banco (ex.: origem = interna)
        $this->timeline->registrar($fiscalizacao, 'criada', 'Fiscalização criada', false, $request->user()->id);
        $fiscalizacao->load(['estabelecimento', 'fiscal']);
        $this->aviso->notificarNova($fiscalizacao);

        return response()->json(new FiscalizacaoResource($fiscalizacao), 201);
    }

    public function update(UpdateFiscalizacaoRequest $request, int $id): JsonResponse
    {
        $fiscalizacao = Fiscalizacao::find($id);

        if (! $fiscalizacao) {
            return response()->json(['error' => 'Fiscalização não encontrada'], 404);
        }

        $dados = $request->validated();
        $visivelAoDenunciante = $request->boolean('visivel_ao_denunciante');
        $situacaoAnterior = $fiscalizacao->resultado;
        $observacaoAnterior = trim((string) $fiscalizacao->observacoes);
        unset($dados['visivel_ao_denunciante']);

        $fiscalizacao->update($dados);

        // O histórico é gerado a partir do que o fiscal edita na própria fiscalização:
        // não há um segundo campo para descrever o mesmo trabalho.
        $userId = $request->user()?->id;
        if (($dados['resultado'] ?? $situacaoAnterior) !== $situacaoAnterior) {
            $this->timeline->registrar(
                $fiscalizacao,
                'situacao_alterada',
                "Situação: {$situacaoAnterior} → {$dados['resultado']}",
                false,
                $userId,
                ['de' => $situacaoAnterior, 'para' => $dados['resultado']]
            );
        }

        $observacaoNova = trim((string) ($dados['observacoes'] ?? ''));
        if (array_key_exists('observacoes', $dados) && $observacaoNova !== '' && $observacaoNova !== $observacaoAnterior) {
            // A caixa "visível ao denunciante" publica exatamente esta observação.
            $this->timeline->registrar($fiscalizacao, 'observacao', $observacaoNova, $visivelAoDenunciante, $userId);
        }

        $fiscalizacao->load(['estabelecimento', 'fiscal']);

        return response()->json(new FiscalizacaoResource($fiscalizacao));
    }

    public function destroy(int $id): JsonResponse
    {
        $fiscalizacao = Fiscalizacao::find($id);

        if (! $fiscalizacao) {
            return response()->json(['error' => 'Fiscalização não encontrada'], 404);
        }

        $fiscalizacao->delete();

        return response()->json(['message' => 'Fiscalização excluída com sucesso']);
    }
}
