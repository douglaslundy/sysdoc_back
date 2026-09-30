<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCall;
use App\Models\AttendanceTicket;

class AttendancePanelService
{
    public function state(): array
    {
        // As colunas guardam o horário no fuso do app (America/Sao_Paulo); converter para UTC
        // deslocava a janela em 3h e escondia as chamadas entre 00:00 e 03:00.
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $latestCall = AttendanceCall::query()
            ->with(['ticket', 'client', 'room', 'user'])
            ->whereBetween('called_at', [$startOfDay, $endOfDay])
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->first();

        $inService = AttendanceTicket::query()
            ->with(['client', 'room', 'assignedUser'])
            ->where('status', AttendanceTicket::STATUS_EM_ATENDIMENTO)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $lastCalls = AttendanceCall::query()
            ->with(['ticket', 'client', 'room', 'user'])
            ->whereBetween('called_at', [$startOfDay, $endOfDay])
            ->when($latestCall, function ($query) use ($latestCall) {
                $query->where('id', '!=', $latestCall->id);
            })
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return [
            'currentCall' => $this->mapCall($latestCall),
            'currentInService' => $inService->map(function (AttendanceTicket $ticket) {
                return [
                    'ticketCode' => $ticket->display_code,
                    'clientName' => $ticket->client?->name,
                    'roomName' => $ticket->room?->name,
                    'userName' => $ticket->assignedUser?->name,
                    'startedAt' => optional($ticket->started_at)->toISOString(),
                ];
            })->values()->all(),
            'lastCalls' => $lastCalls->map(fn (AttendanceCall $call) => $this->mapCall($call))->values()->all(),
        ];
    }

    private function mapCall(?AttendanceCall $call): ?array
    {
        if (! $call) {
            return null;
        }

        return [
            'ticketCode' => $call->ticket?->display_code,
            'clientName' => $call->client?->name,
            'roomName' => $call->room?->name,
            'userName' => $call->user?->name,
            'calledAt' => optional($call->called_at)->toISOString(),
        ];
    }
}
