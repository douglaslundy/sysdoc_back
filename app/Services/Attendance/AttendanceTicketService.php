<?php

namespace App\Services\Attendance;

use App\Models\AttendanceTicket;
use App\Models\Client;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceTicketService
{
    public function __construct(
        private readonly AttendanceTicketNumberService $numberService
    ) {
    }

    public function issueTicket(int $clientId, ?int $createdByUserId = null, string $prefix = 'A'): AttendanceTicket
    {
        Client::query()->findOrFail($clientId);

        $ticket = DB::transaction(function () use ($clientId, $createdByUserId, $prefix) {
            $now = Carbon::now();
            $number = $this->numberService->nextNumberForDate($now);

            $ticket = AttendanceTicket::query()->create([
                'number' => $number,
                'display_code' => $this->numberService->buildDisplayCode($number, $prefix),
                'sequence_date' => $now->toDateString(),
                'client_id' => $clientId,
                'status' => AttendanceTicket::STATUS_AGUARDANDO,
                'issued_at' => $now,
                'created_by_user_id' => $createdByUserId,
            ]);

            AuditService::record('ATTENDANCE_TICKET_ISSUED', $ticket, null, $ticket->toArray());

            return $ticket;
        });

        return $ticket->load('client');
    }

    public function listTickets(array $filters = []): Collection
    {
        $query = AttendanceTicket::query()
            ->with(['client', 'assignedUser', 'room'])
            ->orderByDesc('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['clientId'])) {
            $query->where('client_id', $filters['clientId']);
        }
        if (!empty($filters['roomId'])) {
            $query->where('room_id', $filters['roomId']);
        }
        if (!empty($filters['assignedUserId'])) {
            $query->where('assigned_user_id', $filters['assignedUserId']);
        }
        if (!empty($filters['issuedFrom'])) {
            $query->where('issued_at', '>=', $filters['issuedFrom']);
        }
        if (!empty($filters['issuedTo'])) {
            $query->where('issued_at', '<=', $filters['issuedTo']);
        }
        if (!empty($filters['serviceFrom'])) {
            $query->whereRaw('DATE(COALESCE(finished_at, started_at, called_at, issued_at)) >= ?', [$filters['serviceFrom']]);
        }
        if (!empty($filters['serviceTo'])) {
            $query->whereRaw('DATE(COALESCE(finished_at, started_at, called_at, issued_at)) <= ?', [$filters['serviceTo']]);
        }

        return $query->get();
    }
}
