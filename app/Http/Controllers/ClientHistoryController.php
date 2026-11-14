<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Services\Audit\AuditDescriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientHistoryController extends Controller
{
    private const PER_PAGE = 30;

    /**
     * Historico do cidadao: tudo o que foi feito com/sobre ele, do mais recente para o mais antigo.
     * Consulta indexada por (client_id, id) e paginada.
     */
    /**
     * O cidadão é global, mas o histórico não mostra fila, viagem e atendimento de outra secretaria:
     * eventos desses modelos só entram se o registro pertencer à secretaria ativa ("Todas" vê tudo).
     */
    private static function apenasDaSecretariaAtiva($q): void
    {
        $active = app(\App\Services\Secretarias\ActiveSecretaria::class);
        if ($active->isAll()) {
            return;
        }
        $sid = (int) $active->id();
        $filas = DB::table('queue')->select('id')->where('secretaria_id', $sid);
        $viagens = DB::table('trips')->select('id')->where('secretaria_id', $sid);
        $planos = DB::table('queue_treatment_plans')->select('id')->whereIn('queue_id', $filas);
        $pedidos = DB::table('pedidos_exame')->select('id')->where('secretaria_id', $sid);
        $senhas = DB::table('attendance_tickets')->select('id')->where('secretaria_id', $sid);
        $donos = [
            // pedido/resultado de exame: dado clínico, nunca fora da secretaria do pedido
            'PedidoExame' => $pedidos,
            'PedidoExameItem' => DB::table((new \App\Models\PedidoExameItem())->getTable())->select('id')->whereIn('pedido_exame_id', $pedidos),
            'ResultadoExame' => DB::table((new \App\Models\ResultadoExame())->getTable())->select('id')->whereIn('pedido_exame_id', $pedidos),
            'AttendanceCall' => DB::table((new \App\Models\AttendanceCall())->getTable())->select('id')->whereIn('attendance_ticket_id', $senhas),
            'AttendanceRecord' => DB::table((new \App\Models\AttendanceRecord())->getTable())->select('id')->whereIn('attendance_ticket_id', $senhas),
            'Queue' => $filas,
            'QueueAttachment' => DB::table('queue_attachments')->select('id')->whereIn('queue_id', $filas),
            'QueueTreatmentPlan' => $planos,
            'QueueTreatmentSession' => DB::table('queue_treatment_sessions')->select('id')->whereIn('treatment_plan_id', $planos),
            'Trip' => $viagens,
            'TripClient' => DB::table('trip_clients')->select('id')->whereIn('trip_id', $viagens),
            'TripScheduleEntry' => DB::table('trip_schedule_entries')->select('id')->where('secretaria_id', $sid),
            'AttendanceTicket' => $senhas,
        ];
        $q->whereNull('model_type')->orWhereNotIn('model_type', array_keys($donos));
        foreach ($donos as $tipo => $ids) {
            $q->orWhere(fn ($w) => $w->where('model_type', $tipo)->whereIn('model_id', $ids));
        }
    }

    public function index(Request $request, int $client): JsonResponse
    {
        if (! $request->user()?->canViewClientHistory()) {
            return response()->json(['message' => 'Você não possui permissão para ver o histórico do cidadão.'], 403);
        }

        if (! Client::whereKey($client)->exists()) {
            return response()->json(['message' => 'Cidadão não encontrado.'], 404);
        }

        $page = AuditLog::query()
            ->where('client_id', $client)
            ->where(fn ($q) => self::apenasDaSecretariaAtiva($q))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $page->getCollection()->transform(function (AuditLog $log) {
            $description = AuditDescriber::describe($log);

            return [
                'id' => $log->id,
                'titulo' => $description['titulo'],
                'detalhe' => $description['detalhe'],
                'usuario' => $log->user_name,
                'acao' => $log->action,
                'data' => $log->created_at?->toISOString(),
            ];
        });

        return response()->json($page);
    }
}
