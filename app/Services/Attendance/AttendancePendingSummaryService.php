<?php

namespace App\Services\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendancePendingSummaryService
{
    public function getByClient(int $clientId): array
    {
        $summary = [];

        if (Schema::hasTable('pedidos_exame')) {
            $pendingExams = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('pedidos_exame'), 'secretaria_id')
                ->where('client_id', $clientId)
                ->whereNotIn('status', ['liberado', 'cancelado'])
                ->count();

            $summary[] = [
                'type' => 'exames',
                'label' => 'Pedidos de exame pendentes',
                'count' => $pendingExams,
            ];
        }

        if (Schema::hasTable('trip_clients')) {
            $pendingTrips = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('trip_clients')->join('trips', 'trips.id', '=', 'trip_clients.trip_id'), 'trips.secretaria_id')
                ->where('trip_clients.client_id', $clientId)
                ->where(function ($q) {
                    $q->whereNull('trip_clients.is_confirmed')->orWhere('trip_clients.is_confirmed', false);
                })
                ->count();

            $summary[] = [
                'type' => 'viagens',
                'label' => 'Viagens pendentes',
                'count' => $pendingTrips,
            ];
        }

        if (Schema::hasTable('queue')) {
            $pendingQueue = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('queue'), 'secretaria_id')
                ->where('id_client', $clientId)
                ->where('done', 0)
                ->count();

            $summary[] = [
                'type' => 'fila',
                'label' => 'Itens pendentes em fila',
                'count' => $pendingQueue,
            ];
        }

        return $summary;
    }
}
