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
            $pendingExams = DB::table('pedidos_exame')
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
            $pendingTrips = DB::table('trip_clients')
                ->where('id_client', $clientId)
                ->where(function ($q) {
                    $q->whereNull('is_confirmed')->orWhere('is_confirmed', false);
                })
                ->count();

            $summary[] = [
                'type' => 'viagens',
                'label' => 'Viagens pendentes',
                'count' => $pendingTrips,
            ];
        }

        if (Schema::hasTable('queue')) {
            $pendingQueue = DB::table('queue')
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

