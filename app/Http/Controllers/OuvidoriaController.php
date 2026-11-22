<?php

namespace App\Http\Controllers;

use App\Http\Requests\OuvidoriaComentarioRequest;
use App\Http\Requests\OuvidoriaResponderRequest;
use App\Http\Requests\OuvidoriaStatusRequest;
use App\Models\OuvidoriaHistorico;
use App\Models\OuvidoriaManifestacao;
use App\Models\User;
use App\Services\Ouvidoria\OuvidoriaVisibilidade;
use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Acesso por route_permissions.php (página /sistema/ouvidoria). Toda leitura passa por
 * OuvidoriaVisibilidade::consulta() e toda escrita por podeAgir(): nunca OuvidoriaManifestacao::find direto.
 */
class OuvidoriaController extends Controller
{
    public function __construct(protected OuvidoriaVisibilidade $visibilidade)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $q = $this->visibilidade->consulta()->with(['responsavel', 'secretaria'])->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('tipo')) {
            $q->where('tipo', $request->query('tipo'));
        }
        if ($request->boolean('vencidas')) {
            $q->whereNotIn('status', OuvidoriaManifestacao::FINAIS)->where('prazo_em', '<', now());
        }
        if ($request->filled('busca')) {
            $busca = '%'.addcslashes((string) $request->query('busca'), '%_\\').'%';
            $q->where(fn ($w) => $w->where('protocolo', 'like', $busca)->orWhere('assunto', 'like', $busca));
        }
        // Só o admin em "todas" escolhe a secretaria; para os demais o escopo já a define.
        if ($request->filled('secretaria_id') && app(ActiveSecretaria::class)->isAll()) {
            $q->where('secretaria_id', (int) $request->query('secretaria_id'));
        }

        $pagina = $q->select(['id', 'protocolo', 'tipo', 'assunto', 'status', 'anonimo', 'secretaria_id', 'responsavel_id', 'prazo_em', 'created_at'])
            ->paginate(20)
            ->through(fn (OuvidoriaManifestacao $m) => [
                'id' => $m->id, 'protocolo' => $m->protocolo, 'tipo' => $m->tipo,
                'tipo_rotulo' => OuvidoriaManifestacao::TIPOS[$m->tipo] ?? $m->tipo, 'assunto' => $m->assunto,
                'status' => $m->status, 'anonimo' => $m->anonimo, 'secretaria' => $m->secretaria?->nome,
                'responsavel' => $m->responsavel?->name, 'prazo_em' => $m->prazo_em, 'created_at' => $m->created_at,
                'vencida' => ! $m->estaFinal() && $m->prazo_em?->isPast(),
            ]);

        return response()->json($pagina);
    }

    public function show(int $id): JsonResponse
    {
        $m = $this->visivel($id);
        $m->load(['responsavel', 'secretaria', 'anexos:id,manifestacao_id,original_name,mime_type,size_bytes', 'historico.user']);

        return response()->json($m->toArray() + [
            'tipo_rotulo' => OuvidoriaManifestacao::TIPOS[$m->tipo] ?? $m->tipo,
            'pode_agir' => $this->visibilidade->podeAgir($m),
        ]);
    }

    public function anexo(int $id, int $anexoId)
    {
        $anexo = $this->visivel($id)->anexos()->findOrFail($anexoId);

        return Storage::disk($anexo->disk)->download($anexo->path, $anexo->original_name ?: 'anexo', [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function responder(OuvidoriaResponderRequest $request, int $id): JsonResponse
    {
        $m = $this->visivel($id);
        $this->exigirAgir($m);

        $resp = DB::transaction(function () use ($request, $m) {
            $m = $this->travar($m);
            if ($m->estaFinal()) {
                return response()->json(['message' => 'Manifestação encerrada: peça ao administrador para reabri-la.'], 422);
            }
            $anterior = $m->resposta;
            $statusAntes = $m->status;
            $m->update([
                'resposta' => $request->input('resposta'), 'status' => 'respondida',
                'respondida_por_id' => $request->user()->id, 'respondida_em' => now(),
            ]);
            $this->historico($m, $request->user(), $anterior === null ? 'respondida' : 'resposta_alterada', $statusAntes, 'respondida', $anterior, interno: $anterior !== null);

            return response()->json($m->fresh());
        });

        return $resp;
    }

    public function status(OuvidoriaStatusRequest $request, int $id): JsonResponse
    {
        $m = $this->visivel($id);
        $this->exigirAgir($m);
        $novo = (string) $request->input('status');

        return DB::transaction(function () use ($request, $m, $novo) {
            $m = $this->travar($m);
            $admin = $request->user()->profile === 'admin';
            $permitido = $admin ? $m->status !== $novo : in_array($novo, OuvidoriaManifestacao::TRANSICOES[$m->status] ?? [], true);
            if (! $permitido) {
                return response()->json(['message' => 'Esta mudança de status não é possível a partir de "'.$m->status.'".'], 422);
            }
            $antes = $m->status;
            $m->update(['status' => $novo]);
            $this->historico($m, $request->user(), 'status', $antes, $novo, $request->input('observacao'));

            return response()->json($m->fresh());
        });
    }

    public function comentar(OuvidoriaComentarioRequest $request, int $id): JsonResponse
    {
        $m = $this->visivel($id);
        $this->exigirAgir($m);
        $this->historico($m, $request->user(), 'comentario', null, null, $request->input('observacao'), interno: true);

        return response()->json(['ok' => true]);
    }

    protected function visivel(int $id): OuvidoriaManifestacao
    {
        return $this->visibilidade->consulta()->findOrFail($id);
    }

    protected function exigirAgir(OuvidoriaManifestacao $m): void
    {
        if (! $this->visibilidade->podeAgir($m)) {
            abort(403, 'Manifestação em somente leitura para a sua secretaria.');
        }
    }

    /** Relê a linha com lock dentro da transação (evita duas respostas/transições simultâneas). */
    protected function travar(OuvidoriaManifestacao $m): OuvidoriaManifestacao
    {
        return OuvidoriaManifestacao::withoutSecretaria()->lockForUpdate()->findOrFail($m->id);
    }

    protected function historico(OuvidoriaManifestacao $m, User $user, string $acao, ?string $de, ?string $para, ?string $obs, bool $interno = false, ?int $secDe = null, ?int $secPara = null): void
    {
        OuvidoriaHistorico::create([
            'manifestacao_id' => $m->id, 'user_id' => $user->id, 'acao' => $acao, 'status_de' => $de, 'status_para' => $para,
            'secretaria_de' => $secDe, 'secretaria_para' => $secPara, 'observacao' => $obs,
            'interno' => $interno || $acao === 'comentario', 'created_at' => now(),
        ]);
    }
}
