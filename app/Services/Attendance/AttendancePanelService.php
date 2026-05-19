<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCall;
use App\Models\AttendanceTicket;

class AttendancePanelService
{
    public function state(): array
    {
        $latestCall = AttendanceCall::query()
            ->with(['ticket', 'client', 'room', 'user'])
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->first();

        $inService = AttendanceTicket::query()
            ->with(['client', 'room', 'assignedUser'])
            ->where('status', AttendanceTicket::STATUS_EM_ATENDIMENTO)
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $lastCalls = AttendanceCall::query()
            ->with(['ticket', 'client', 'room', 'user'])
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
            'lastCalls' => $lastCalls->map(fn(AttendanceCall $call) => $this->mapCall($call))->values()->all(),
        ];
    }

    private function mapCall(?AttendanceCall $call): ?array
    {
        if (!$call) {
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

