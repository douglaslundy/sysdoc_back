<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceTicket;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceServiceFlowService
{
    public function __construct(
        private readonly AttendanceStatusService $statusService
    ) {
    }

    public function getServiceData(int $ticketId): AttendanceTicket
    {
        return AttendanceTicket::query()
            ->with(['client', 'room', 'assignedUser', 'record'])
            ->findOrFail($ticketId);
    }

    public function start(int $ticketId): AttendanceTicket
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $this->statusService->assertCanStart($ticket->status);

            $now = Carbon::now();
            $old = $ticket->toArray();

            $ticket->update([
                'status' => AttendanceTicket::STATUS_EM_ATENDIMENTO,
                'started_at' => $ticket->started_at ?? $now,
            ]);

            AttendanceRecord::query()->updateOrCreate(
                ['attendance_ticket_id' => $ticket->id],
                [
                    'client_id' => $ticket->client_id,
                    'user_id' => $ticket->assigned_user_id,
                    'room_id' => $ticket->room_id,
                    'started_at' => $ticket->started_at ?? $now,
                ]
            );

            AuditService::record('ATTENDANCE_STARTED', $ticket, $old, $ticket->fresh()->toArray());

            return $ticket->fresh(['client', 'room', 'assignedUser', 'record']);
        });
    }

    public function saveNotes(int $ticketId, ?string $notes): AttendanceRecord
    {
        return DB::transaction(function () use ($ticketId, $notes) {
            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $record = AttendanceRecord::query()->updateOrCreate(
                ['attendance_ticket_id' => $ticket->id],
                [
                    'client_id' => $ticket->client_id,
                    'user_id' => $ticket->assigned_user_id,
                    'room_id' => $ticket->room_id,
                    'started_at' => $ticket->started_at,
                    'notes' => $notes,
                ]
            );

            AuditService::record('ATTENDANCE_NOTES_SAVED', $ticket, null, ['attendance_record_id' => $record->id]);

            return $record;
        });
    }

    public function finish(int $ticketId, ?string $notes = null): AttendanceTicket
    {
        return DB::transaction(function () use ($ticketId, $notes) {
            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $this->statusService->assertCanFinish($ticket->status);

            $now = Carbon::now();
            $old = $ticket->toArray();

            $ticket->update([
                'status' => AttendanceTicket::STATUS_FINALIZADA,
                'started_at' => $ticket->started_at ?? $now,
                'finished_at' => $now,
            ]);

            AttendanceRecord::query()->updateOrCreate(
                ['attendance_ticket_id' => $ticket->id],
                [
                    'client_id' => $ticket->client_id,
                    'user_id' => $ticket->assigned_user_id,
                    'room_id' => $ticket->room_id,
                    'started_at' => $ticket->started_at ?? $now,
                    'finished_at' => $now,
                    'notes' => $notes,
                ]
            );

            AuditService::record('ATTENDANCE_FINISHED', $ticket, $old, $ticket->fresh()->toArray());

            return $ticket->fresh(['client', 'room', 'assignedUser', 'record']);
        });
    }

    public function noShow(int $ticketId): AttendanceTicket
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $this->statusService->assertCanNoShow($ticket->status);

            $old = $ticket->toArray();
            $ticket->update([
                'status' => AttendanceTicket::STATUS_NAO_COMPARECEU,
                'no_show_at' => Carbon::now(),
            ]);

            AuditService::record('ATTENDANCE_NO_SHOW', $ticket, $old, $ticket->fresh()->toArray());

            return $ticket->fresh(['client', 'room', 'assignedUser', 'record']);
        });
    }

    public function cancel(int $ticketId): AttendanceTicket
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = AttendanceTicket::query()->lockForUpdate()->findOrFail($ticketId);
            $this->statusService->assertCanCancel($ticket->status);

            $old = $ticket->toArray();
            $ticket->update([
                'status' => AttendanceTicket::STATUS_CANCELADA,
                'cancelled_at' => Carbon::now(),
            ]);

            AuditService::record('ATTENDANCE_CANCELLED', $ticket, $old, $ticket->fresh()->toArray());

            return $ticket->fresh(['client', 'room', 'assignedUser', 'record']);
        });
    }
}
