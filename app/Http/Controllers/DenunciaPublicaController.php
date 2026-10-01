<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultaDenunciaRequest;
use App\Http\Requests\StoreDenunciaRequest;
use App\Models\Fiscalizacao;
use App\Models\FiscalizacaoAttachment;
use App\Models\KanbanTask;
use App\Services\Fiscalizacao\FiscalizacaoProtocolo;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use App\Services\Fiscalizacao\VigilanciaAvisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Denúncia pública (sem login). A denúncia é uma fiscalização "Pendente de apuração";
 * o denunciante recebe protocolo + senha para acompanhar.
 */
class DenunciaPublicaController extends Controller
{
    private const SENHA_ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const SENHA_TAMANHO = 8;

    private const DISK = 'private';

    private const MAX_FALHAS = 5;

    private const BLOQUEIO_SEGUNDOS = 900; // 15 minutos

    private const TITULOS = [
        'denuncia_recebida' => 'Denúncia recebida',
        'mensagem_publica' => 'Mensagem ao denunciante',
        'observacao' => 'Atualização',
        'situacao_alterada' => 'Situação alterada',
    ];

    public function __construct(private FiscalizacaoTimeline $timeline, private VigilanciaAvisoService $aviso)
    {
    }

    public function store(StoreDenunciaRequest $request): JsonResponse
    {
        // Campo isca preenchido = robô: responde como se tivesse funcionado, sem gravar nada.
        if ($request->filled('website')) {
            return response()->json($this->recibo(
                sprintf('FIS-%s-%06d', now()->format('Y'), random_int(900000, 999999)),
                $this->gerarSenha()
            ), 201);
        }

        $senha = $this->gerarSenha();

        $fiscalizacao = DB::transaction(function () use ($request, $senha) {
            $fiscalizacao = Fiscalizacao::create([
                'resultado' => 'Pendente de apuração',
                'origem' => 'peticao',
                'motivo_id' => $request->input('motivo_id'),
                'assunto' => $request->input('assunto'),
                'descricao_denuncia' => $request->input('descricao_denuncia'),
                'local_endereco' => $request->input('local_endereco'),
                'estabelecimento_nome_informado' => $request->input('estabelecimento_nome_informado'),
                'denunciante_nome' => $request->input('denunciante_nome'),
                'denunciante_contato' => $request->input('denunciante_contato'),
            ]);

            // Protocolo e hash da senha são gravados sem eventos: a senha não pode ir para a auditoria.
            $fiscalizacao->forceFill([
                'protocolo' => FiscalizacaoProtocolo::for($fiscalizacao->id, $fiscalizacao->created_at),
                'senha_consulta_hash' => Hash::make($senha),
            ])->saveQuietly();

            $anexos = 0;
            foreach ((array) $request->file('files', []) as $file) {
                FiscalizacaoAttachment::create([
                    'fiscalizacao_id' => $fiscalizacao->id,
                    'uploaded_by' => null,
                    'origem' => 'denunciante',
                    'disk' => self::DISK,
                    'path' => $file->store('fiscalizacao-attachments/'.$fiscalizacao->id, self::DISK),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size_bytes' => (int) $file->getSize(),
                ]);
                $anexos++;
            }

            $this->criarCardNaUnidade($fiscalizacao);

            $this->timeline->registrar(
                $fiscalizacao,
                'denuncia_recebida',
                'Denúncia recebida',
                true,
                null,
                ['anexos' => $anexos]
            );

            return $fiscalizacao;
        });

        // Avisa os profissionais da Vigilância (WhatsApp) depois da resposta; nunca leva dados do denunciante.
        $this->aviso->notificarNova($fiscalizacao);

        return response()->json($this->recibo($fiscalizacao->protocolo, $senha), 201);
    }

    /**
     * Consulta pública por protocolo + senha. Resposta idêntica para protocolo inexistente e
     * senha errada; 5 erros no mesmo protocolo bloqueiam por 15 minutos.
     */
    public function consultar(ConsultaDenunciaRequest $request): JsonResponse
    {
        $protocolo = strtoupper(trim((string) $request->input('protocolo')));
        $senha = strtoupper(trim((string) $request->input('senha')));
        $chaveFalhas = 'denuncia-falhas:'.$protocolo;

        if ((int) Cache::get($chaveFalhas, 0) >= self::MAX_FALHAS) {
            return response()->json([
                'error' => 'Muitas tentativas. Tente novamente em alguns minutos.',
            ], 429);
        }

        $fiscalizacao = Fiscalizacao::query()
            ->with('motivo:id,nome')
            ->where('protocolo', $protocolo)
            ->where('origem', 'peticao')
            ->whereNotNull('senha_consulta_hash')
            ->first();

        // O hash fica oculto no model; lê o valor bruto para conferir a senha.
        $confere = $fiscalizacao && Hash::check($senha, (string) $fiscalizacao->getRawOriginal('senha_consulta_hash'));

        if (! $confere) {
            Cache::add($chaveFalhas, 0, self::BLOQUEIO_SEGUNDOS);
            Cache::increment($chaveFalhas);

            return response()->json(['error' => 'Protocolo ou senha inválidos.'], 404);
        }

        Cache::forget($chaveFalhas);

        $movimentacoes = $fiscalizacao->movimentacoes()
            ->where('publico', true)
            ->reorder('id', 'desc')
            ->get()
            ->map(fn ($mov) => [
                'titulo' => self::TITULOS[$mov->acao] ?? 'Atualização',
                'descricao' => $mov->descricao,
                'data' => $mov->created_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'protocolo' => $fiscalizacao->protocolo,
            // Não expõe o resultado interno: só se já foi apurada ou não.
            'situacao' => $fiscalizacao->resultado === 'Pendente de apuração' ? 'Pendente de apuração' : 'Apurada',
            'motivo' => $fiscalizacao->motivo?->nome,
            'assunto' => $fiscalizacao->assunto,
            'local_endereco' => $fiscalizacao->local_endereco,
            'registrada_em' => $fiscalizacao->created_at?->toISOString(),
            'movimentacoes' => $movimentacoes,
        ]);
    }

    /**
     * O motivo aponta a unidade responsável: a petição vira um card no kanban dela. O card não
     * leva nenhum dado do denunciante (nome/contato), só o que a equipe precisa para agir.
     */
    private function criarCardNaUnidade(Fiscalizacao $fiscalizacao): void
    {
        $fiscalizacao->loadMissing('motivo');
        $motivo = $fiscalizacao->motivo;

        if (! $motivo?->unit_id) {
            return;
        }

        KanbanTask::create([
            'unit_id' => $motivo->unit_id,
            'fiscalizacao_id' => $fiscalizacao->id,
            'titulo' => mb_substr("Petição {$fiscalizacao->protocolo} — {$motivo->nome}", 0, 200),
            'descricao' => "Assunto: {$fiscalizacao->assunto}\nLocal: {$fiscalizacao->local_endereco}",
            'status' => 'novo',
            'prioridade' => 'normal',
            'visibility' => 'public',
            'ordem' => 0,
        ]);
    }

    private function recibo(string $protocolo, string $senha): array
    {
        return [
            'protocolo' => $protocolo,
            'senha' => $senha,
            'url_consulta' => rtrim((string) config('app.frontend_url'), '/').'/petition/track?protocolo='.$protocolo,
        ];
    }

    private function gerarSenha(): string
    {
        $senha = '';
        $max = strlen(self::SENHA_ALFABETO) - 1;
        for ($i = 0; $i < self::SENHA_TAMANHO; $i++) {
            $senha .= self::SENHA_ALFABETO[random_int(0, $max)];
        }

        return $senha;
    }
}
