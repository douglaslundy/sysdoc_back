<?php

namespace App\Http\Controllers;

use App\Jobs\AplicarSincronizacaoJob;
use App\Jobs\SincronizacaoCidadaoJob;
use App\Models\SincronizacaoCidadao;
use App\Models\SincronizacaoItem;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConformidadeCidadaoController extends Controller
{
    private function autorizado(Request $request): bool
    {
        return app(PagePermissionService::class)->canAccess($request->user(), '/conformidade-cidadao');
    }

    public function analisar(Request $request): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão para executar a sincronização.'], 403);
        }

        $emAndamento = SincronizacaoCidadao::whereIn('status', ['pending', 'analyzing', 'applying'])->exists();
        if ($emAndamento) {
            return response()->json(['message' => 'Já existe uma sincronização em andamento.'], 409);
        }

        $sync = SincronizacaoCidadao::create([
            'job_id'       => (string) Str::uuid(),
            'status'       => 'pending',
            'iniciado_por' => $request->user()->id,
        ]);

        SincronizacaoCidadaoJob::dispatchAfterResponse($sync);

        return response()->json(['job_id' => $sync->job_id], 202);
    }

    public function status(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        $data = $sync->only([
            'job_id', 'status', 'total_esus', 'total_esus_previsto', 'total_sysdoc',
            'preview_criados', 'preview_atualizados', 'preview_obitos', 'preview_sem_alteracao',
            'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
            'analisado_em', 'aplicado_em', 'erro_mensagem',
        ]);

        if ($sync->status === 'applying') {
            $data['total_aplicado'] = SincronizacaoItem::where('sincronizacao_id', $sync->id)
                ->where('aplicado', true)
                ->count();
            $data['total_a_aplicar'] = ($sync->preview_criados ?? 0)
                + ($sync->preview_atualizados ?? 0)
                + ($sync->preview_obitos ?? 0);
        }

        if ($sync->status === 'preview_ready') {
            $perPage = min(50, max(10, (int) $request->query('per_page', 20)));
            $page    = max(1, (int) $request->query('page', 1));

            $itens = SincronizacaoItem::where('sincronizacao_id', $sync->id)
                ->select(['id', 'acao', 'cpf', 'cns', 'nome_esus', 'client_id', 'payload'])
                ->forPage($page, $perPage)
                ->get();

            $total = SincronizacaoItem::where('sincronizacao_id', $sync->id)->count();

            $data['itens'] = $itens;
            $data['itens_meta'] = [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ];
        }

        return response()->json($data);
    }

    public function aplicar(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        if ($sync->status !== 'preview_ready') {
            return response()->json([
                'message' => "Não é possível aplicar no status atual: {$sync->status}.",
            ], 409);
        }

        $sync->update(['aplicado_por' => $request->user()->id]);
        AplicarSincronizacaoJob::dispatchAfterResponse($sync);

        return response()->json(['job_id' => $sync->job_id, 'message' => 'Aplicação iniciada.'], 202);
    }

    public function cancelar(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        if (!in_array($sync->status, ['pending', 'analyzing', 'applying'])) {
            return response()->json(['message' => 'Não é possível cancelar no status atual.'], 409);
        }

        $sync->update([
            'status'        => 'failed',
            'erro_mensagem' => 'Cancelado pelo usuário.',
        ]);

        return response()->json(['message' => 'Sincronização cancelada.']);
    }

    public function historico(Request $request): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $perPage = min(50, max(5, (int) $request->query('per_page', 15)));
        $syncs   = SincronizacaoCidadao::select([
                'job_id', 'status', 'total_esus', 'total_sysdoc',
                'preview_criados', 'preview_atualizados', 'preview_obitos',
                'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
                'iniciado_por', 'analisado_em', 'aplicado_em', 'created_at',
            ])
            ->with('iniciadoPor:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $syncs->items(),
            'meta' => [
                'total'        => $syncs->total(),
                'per_page'     => $syncs->perPage(),
                'current_page' => $syncs->currentPage(),
                'last_page'    => $syncs->lastPage(),
            ],
        ]);
    }

    public function erros(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        $perPage = min(100, max(10, (int) $request->query('per_page', 50)));
        $page    = max(1, (int) $request->query('page', 1));

        $itens = SincronizacaoItem::where('sincronizacao_id', $sync->id)
            ->whereNotNull('erro')
            ->select(['id', 'acao', 'cpf', 'cns', 'nome_esus', 'client_id', 'erro'])
            ->forPage($page, $perPage)
            ->get();

        $total = SincronizacaoItem::where('sincronizacao_id', $sync->id)
            ->whereNotNull('erro')
            ->count();

        return response()->json([
            'data' => $itens,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }
}
