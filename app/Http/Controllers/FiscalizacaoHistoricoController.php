<?php

namespace App\Http\Controllers;

use App\Models\Fiscalizacao;
use App\Models\FiscalizacaoMovimentacao;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use Illuminate\Http\JsonResponse;

class FiscalizacaoHistoricoController extends Controller
{
    private const TITULOS = [
        'criada' => 'Fiscalização criada',
        'denuncia_recebida' => 'Denúncia recebida',
        'situacao_alterada' => 'Situação alterada',
        'mensagem_publica' => 'Mensagem ao denunciante',
        'observacao' => 'Observação',
        'anexo_adicionado' => 'Anexo adicionado',
    ];

    public function __construct(private FiscalizacaoTimeline $timeline)
    {
    }

    /** Histórico completo (interno + público), do mais recente para o mais antigo. */
    public function index(Fiscalizacao $fiscalizacao): JsonResponse
    {
        $itens = $fiscalizacao->movimentacoes()
            ->with('user:id,name')
            ->reorder('id', 'desc')
            ->get()
            ->map(fn (FiscalizacaoMovimentacao $mov) => $this->present($mov))
            ->values();

        return response()->json($itens);
    }

    private function present(FiscalizacaoMovimentacao $mov): array
    {
        return [
            'id' => $mov->id,
            'titulo' => self::TITULOS[$mov->acao] ?? ucfirst(str_replace('_', ' ', $mov->acao)),
            'detalhe' => $mov->descricao,
            'usuario' => $mov->user?->name ?? ($mov->acao === 'denuncia_recebida' ? 'Denunciante' : 'Sistema'),
            'data' => $mov->created_at?->toISOString(),
            'publico' => $mov->publico,
        ];
    }
}
