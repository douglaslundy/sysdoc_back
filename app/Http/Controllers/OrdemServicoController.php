<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtribuirOrdemServicoRequest;
use App\Http\Requests\ConcluirOrdemServicoRequest;
use App\Http\Requests\ObservacaoOrdemServicoRequest;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Models\OrdemServico;
use App\Models\OsConfiguracao;
use App\Models\OsFoto;
use App\Models\OsHistorico;
use App\Models\User;
use App\Services\OrdemServico\OrdemServicoNotificador;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrdemServicoController extends Controller
{
    private const DISK = 'private';

    /** @var string[] arquivos gravados na requisição atual */
    private array $gravados = [];

    public function index(Request $request): JsonResponse
    {
        $q = OrdemServico::visiveisPara($request->user())
            ->with(['tipo:id,nome', 'secretaria:id,nome', 'solicitante:id,name', 'tecnico:id,name'])
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($x) use ($request) {
                $t = '%'.str_replace(['%', '_'], ['\%', '\_'], $request->input('q')).'%';
                $x->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('local', 'like', $t));
            })
            ->orderByDesc('id');

        return response()->json($q->paginate(20));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $os = $this->achar($request, $id)->load(['tipo:id,nome', 'secretaria:id,nome', 'solicitante', 'tecnico', 'fotos', 'historico.user']);

        return response()->json(array_merge($os->toArray(), ['pode' => $this->permissoes($request->user(), $os)]));
    }

    public function store(StoreOrdemServicoRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $user = $request->user();
        $tecnicoId = $dados['tecnico_id'] ?? null;
        if ($tecnicoId) {
            // O solicitante só aponta técnico se a regra da secretaria permitir; o gestor/admin sempre pode.
            $pode = OsConfiguracao::solicitanteApontaTecnico((int) $dados['secretaria_id'])
                || \App\Models\Scopes\CadastroProtocoloScope::pode($user, (int) $dados['secretaria_id']);
            if (! $pode || ! $this->tecnicoDaSecretaria((int) $tecnicoId, (int) $dados['secretaria_id'])) {
                return response()->json(['message' => 'Não é possível apontar este técnico.'], 422);
            }
        }

        $os = $this->comLimpezaDeFotos(fn () => $this->tentar(fn () => DB::transaction(function () use ($dados, $user, $tecnicoId, $request) {
            $os = OrdemServico::create(array_merge(collect($dados)->except(['fotos', 'tecnico_id', 'prioridade'])->all(), [
                'numero' => OrdemServico::gerarNumero(),
                'solicitante_id' => $user->id,
                'tecnico_id' => $tecnicoId,
                'prioridade' => $dados['prioridade'] ?? 'normal',
                'status' => $tecnicoId ? 'atribuida' : 'aberta',
            ]));
            $this->historico($os, $user, 'aberta', null, $os->status);
            $this->guardarFotos($os, $request->file('fotos', []), 'abertura', $user->id);

            return $os;
        })));

        $this->avisar(fn ($n) => $n->aberta($os->load('tecnico')));

        return response()->json($os, 201);
    }

    public function atribuir(AtribuirOrdemServicoRequest $request, int $id): JsonResponse
    {
        $os = $this->achar($request, $id);
        if (! $os->ehGestorDaSecretaria($request->user())) {
            return response()->json(['message' => 'Apenas o gestor da secretaria atribui técnico.'], 403);
        }
        if (! in_array($os->status, ['aberta', 'atribuida'], true)) {
            return response()->json(['message' => 'A OS já está em atendimento ou encerrada.'], 422);
        }
        if (! $this->tecnicoDaSecretaria((int) $request->tecnico_id, $os->secretaria_id)) {
            return response()->json(['message' => 'Técnico não pertence à secretaria da OS.'], 422);
        }

        $resp = DB::transaction(function () use ($os, $request) {
            $os = $this->travar($os);
            if (! in_array($os->status, ['aberta', 'atribuida'], true)) {
                return response()->json(['message' => 'A OS já está em atendimento ou encerrada.'], 422);
            }
            $de = $os->status;
            $os->update(['tecnico_id' => $request->tecnico_id, 'status' => 'atribuida']);
            $this->historico($os, $request->user(), 'atribuida', $de, 'atribuida');

            return $os;
        });
        if ($resp instanceof JsonResponse) {
            return $resp;
        }
        $this->avisar(fn ($n) => $n->tecnicoApontado($resp->load('tecnico')));

        return response()->json($resp);
    }

    public function iniciar(ObservacaoOrdemServicoRequest $request, int $id): JsonResponse
    {
        $os = $this->achar($request, $id);
        if ($os->tecnico_id !== $request->user()->id) {
            return response()->json(['message' => 'Apenas o técnico atribuído atende a OS.'], 403);
        }

        return $this->transicao($request, $os, ['atribuida'], 'em_atendimento', 'em atendimento');
    }

    public function concluir(ConcluirOrdemServicoRequest $request, int $id): JsonResponse
    {
        $os = $this->achar($request, $id);
        if ($os->tecnico_id !== $request->user()->id) {
            return response()->json(['message' => 'Apenas o técnico atribuído conclui a OS.'], 403);
        }
        if ($os->status !== 'em_atendimento') {
            return response()->json(['message' => 'A OS precisa estar em atendimento.'], 422);
        }

        $resp = $this->comLimpezaDeFotos(fn () => DB::transaction(function () use ($os, $request) {
            $os = $this->travar($os);
            if ($os->status !== 'em_atendimento') {
                return response()->json(['message' => 'A OS precisa estar em atendimento.'], 422);
            }
            $os->update([
                'status' => 'concluida', 'concluida_em' => now(), 'fechamento_relato' => $request->relato,
                'materiais' => $request->materiais, 'tempo_gasto_min' => $request->tempo_gasto_min,
            ]);
            $this->historico($os, $request->user(), 'concluida', 'em_atendimento', 'concluida', $request->relato);
            $this->guardarFotos($os, $request->file('fotos', []), 'fechamento', $request->user()->id);

            return $os;
        }));
        if ($resp instanceof JsonResponse) {
            return $resp;
        }
        $this->avisar(fn ($n) => $n->mudouStatus($resp->load('tecnico'), 'concluída'));

        return response()->json($resp);
    }

    public function cancelar(ObservacaoOrdemServicoRequest $request, int $id): JsonResponse
    {
        $os = $this->achar($request, $id);
        $user = $request->user();
        if ($os->solicitante_id !== $user->id && ! $os->ehGestorDaSecretaria($user)) {
            return response()->json(['message' => 'Apenas o solicitante ou o gestor cancela a OS.'], 403);
        }

        return $this->transicao($request, $os, ['aberta', 'atribuida', 'em_atendimento'], 'cancelada', 'cancelada');
    }

    public function foto(Request $request, int $id, int $fotoId)
    {
        $os = $this->achar($request, $id);
        $foto = OsFoto::where('ordem_servico_id', $os->id)->findOrFail($fotoId);

        return Storage::disk($foto->disk)->response($foto->path, null, [
            'Content-Type' => $foto->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function pdf(Request $request, int $id)
    {
        $os = $this->achar($request, $id)->load(['tipo', 'secretaria', 'solicitante', 'tecnico', 'fotos', 'historico.user']);
        $fotos = $os->fotos->map(function (OsFoto $f) {
            $bin = Storage::disk($f->disk)->exists($f->path) ? Storage::disk($f->disk)->get($f->path) : null;

            return $bin ? ['fase' => $f->fase, 'src' => 'data:'.($f->mime_type ?: 'image/jpeg').';base64,'.base64_encode($bin)] : null;
        })->filter()->values();

        return Pdf::loadView('pdf.ordem-servico', ['os' => $os, 'fotos' => $fotos])->setPaper('a4')
            ->download('OS-'.str_replace('/', '-', $os->numero).'.pdf');
    }

    /** Secretarias ativas (destino possível de uma OS; qualquer uma atende qualquer solicitante). */
    public function secretarias(): JsonResponse
    {
        return response()->json(\App\Models\Secretaria::where('ativa', true)->orderBy('nome')->get(['id', 'nome']));
    }

    /** Técnicos da secretaria destino e se o solicitante pode apontar um (usado no formulário de abertura). */
    public function tecnicos(Request $request): JsonResponse
    {
        $secretaria = (int) $request->query('secretaria_id');
        $gestor = \App\Models\Scopes\CadastroProtocoloScope::pode($request->user(), $secretaria);
        $permitido = $gestor || OsConfiguracao::solicitanteApontaTecnico($secretaria);

        return response()->json([
            'permitido' => $permitido,
            'tecnicos' => $permitido
                ? User::where('active', true)->where('profile', 'tecnico')
                    ->whereHas('secretarias', fn ($x) => $x->where('secretarias.id', $secretaria))
                    ->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    private function transicao(Request $request, OrdemServico $os, array $de, string $para, string $rotulo): JsonResponse
    {
        $resp = DB::transaction(function () use ($os, $request, $de, $para) {
            $os = $this->travar($os);
            if (! in_array($os->status, $de, true)) {
                return response()->json(['message' => 'Esta ação não é possível no status atual da OS.'], 422);
            }
            $anterior = $os->status;
            $os->update(['status' => $para]);
            $this->historico($os, $request->user(), $para, $anterior, $para, $request->input('observacao'));

            return $os;
        });
        if ($resp instanceof JsonResponse) {
            return $resp;
        }
        $this->avisar(fn ($n) => $n->mudouStatus($resp->load('tecnico'), $rotulo));

        return response()->json($resp);
    }

    /** Relê a OS travada (dentro de transação) para revalidar o status contra transições concorrentes. */
    private function travar(OrdemServico $os): OrdemServico
    {
        return OrdemServico::whereKey($os->id)->lockForUpdate()->firstOrFail();
    }

    /** Número sequencial concorrente: repete se outra abertura levou o mesmo número (1062) ou houve deadlock (1213). */
    private function tentar(callable $fn, int $vezes = 4)
    {
        for ($i = 1; ; $i++) {
            try {
                return $fn();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($i >= $vezes || ! in_array((int) ($e->errorInfo[1] ?? 0), [1062, 1213], true)) {
                    throw $e;
                }
            }
        }
    }

    /** Avisos saem depois da resposta: WhatsApp/e-mail lentos não seguram a requisição. */
    private function avisar(callable $fn): void
    {
        dispatch(fn () => $fn(app(OrdemServicoNotificador::class)))->afterResponse();
    }

    /** Se a gravação falhar, apaga do disco as fotos já enviadas (a transação só desfaz as linhas). */
    private function comLimpezaDeFotos(callable $fn)
    {
        $this->gravados = [];
        try {
            return $fn();
        } catch (\Throwable $e) {
            foreach ($this->gravados as $path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $e;
        }
    }

    private function achar(Request $request, int $id): OrdemServico
    {
        return OrdemServico::visiveisPara($request->user())->findOrFail($id);
    }

    private function permissoes(User $user, OrdemServico $os): array
    {
        $aberta = ! in_array($os->status, OrdemServico::ENCERRADAS, true);
        $gestor = $os->ehGestorDaSecretaria($user);

        return [
            'atribuir' => $gestor && in_array($os->status, ['aberta', 'atribuida'], true),
            'iniciar' => $os->tecnico_id === $user->id && $os->status === 'atribuida',
            'concluir' => $os->tecnico_id === $user->id && $os->status === 'em_atendimento',
            'cancelar' => $aberta && ($gestor || $os->solicitante_id === $user->id),
        ];
    }

    private function tecnicoDaSecretaria(int $tecnicoId, int $secretariaId): bool
    {
        return User::where('id', $tecnicoId)->where('profile', 'tecnico')->where('active', true)
            ->whereHas('secretarias', fn ($x) => $x->where('secretarias.id', $secretariaId))->exists();
    }

    private function historico(OrdemServico $os, User $user, string $acao, ?string $de, ?string $para, ?string $obs = null): void
    {
        OsHistorico::create(['ordem_servico_id' => $os->id, 'user_id' => $user->id, 'acao' => $acao, 'status_de' => $de, 'status_para' => $para, 'observacao' => $obs]);
    }

    /** @param  UploadedFile[]  $arquivos */
    private function guardarFotos(OrdemServico $os, array $arquivos, string $fase, int $userId): void
    {
        foreach ($arquivos as $a) {
            $this->gravados[] = $path = $a->store("ordem-servico/{$os->id}", self::DISK);
            OsFoto::create([
                'ordem_servico_id' => $os->id, 'fase' => $fase, 'enviada_por' => $userId, 'disk' => self::DISK,
                'path' => $path, 'original_name' => $a->getClientOriginalName(),
                'mime_type' => $a->getMimeType() ?? $a->getClientMimeType(), 'size_bytes' => (int) $a->getSize(),
            ]);
        }
    }
}
