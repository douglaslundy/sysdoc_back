<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCall;
use App\Models\AttendanceRoom;
use App\Models\AttendanceTicket;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceQueueService
{
    public function __construct(
        private readonly AttendanceStatusService $statusService
    ) {
    }

    public function getQueue(): Collection
    {
        return AttendanceTicket::query()
            ->with(['client', 'room', 'assignedUser'])
            ->where('status', AttendanceTicket::STATUS_AGUARDANDO)
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();
    }

    public function callNext(int $userId, int $roomId): AttendanceTicket
    {
        return DB::transaction(function () use ($userId, $roomId) {
            $user = User::query()->findOrFail($userId);
            $room = AttendanceRoom::query()->where('active', true)->findOrFail($roomId);

            $ticket = AttendanceTicket::query()
                ->where('status', AttendanceTicket::STATUS_AGUARDANDO)
                ->orderBy('issued_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new DomainException('Nenhuma senha aguardando atendimento.');
            }

            return $this->callLockedTicket($ticket, $user->id, $room->id);
        });
    }

    public function callSpecific(int $ticketId, int $userId, int $roomId): AttendanceTicket
    {
        return DB::transaction(function () use ($ticketId, $userId, $roomId) {
            $user = User::query()->findOrFail($userId);
            $room = AttendanceRoom::query()->where('active', true)->findOrFail($roomId);

            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $this->statusService->assertCanCall($ticket->status);

            return $this->callLockedTicket($ticket, $user->id, $room->id);
        });
    }

    private function callLockedTicket(AttendanceTicket $ticket, int $userId, int $roomId): AttendanceTicket
    {
        $now = Carbon::now();
        $old = $ticket->toArray();

        $updatedRows = AttendanceTicket::query()
            ->where('id', $ticket->id)
            ->where('status', AttendanceTicket::STATUS_AGUARDANDO)
            ->update([
            'status' => AttendanceTicket::STATUS_CHAMADA,
            'called_at' => $now,
            'assigned_user_id' => $userId,
            'room_id' => $roomId,
            ]);

        if ($updatedRows !== 1) {
            throw new DomainException('Senha já foi chamada por outro atendente.');
        }

        AttendanceCall::query()->create([
            'attendance_ticket_id' => $ticket->id,
            'client_id' => $ticket->client_id,
            'user_id' => $userId,
            'room_id' => $roomId,
            'called_at' => $now,
        ]);

        AuditService::record('ATTENDANCE_TICKET_CALLED', $ticket, $old, $ticket->fresh()->toArray());

        return $ticket->fresh(['client', 'room', 'assignedUser']);
    }
}
