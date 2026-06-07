<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Attendance\AttendancePanelService;
use App\Services\Attendance\AttendancePendingSummaryService;
use App\Services\Attendance\AttendanceQueueService;
use App\Services\Attendance\AttendanceServiceFlowService;
use App\Services\Attendance\AttendanceTicketService;
use App\Services\AuditService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceTicketService $ticketService,
        private readonly AttendanceQueueService $queueService,
        private readonly AttendanceServiceFlowService $serviceFlow,
        private readonly AttendancePanelService $panelService,
        private readonly AttendancePendingSummaryService $pendingSummaryService
    ) {
    }

    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clientId' => 'required|integer|exists:clients,id',
            'prefix' => 'nullable|string|max:3',
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
        ]);

        $ticket = $this->ticketService->issueTicket(
            (int) $validated['clientId'],
            auth()->id(),
            $validated['prefix'] ?? 'A',
            isset($validated['roomId']) ? (int) $validated['roomId'] : null
        );

        return response()->json($ticket, 201);
    }

    public function listTickets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:aguardando,chamada,em_atendimento,finalizada,cancelada,nao_compareceu',
            'clientId' => 'nullable|integer|exists:clients,id',
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
            'assignedUserId' => 'nullable|integer|exists:users,id',
            'issuedFrom' => 'nullable|date',
            'issuedTo' => 'nullable|date',
            'serviceFrom' => 'nullable|date',
            'serviceTo' => 'nullable|date',
        ]);

        return response()->json($this->ticketService->listTickets($validated));
    }

    public function attendants(): JsonResponse
    {
        return response()->json(
            User::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
        );
    }

    public function showTicket(int $id): JsonResponse
    {
        try {
            return response()->json($this->serviceFlow->getServiceData($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        }
    }

    public function cancelTicket(int $id): JsonResponse
    {
        try {
            return response()->json($this->serviceFlow->cancel($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function noShowTicket(int $id): JsonResponse
    {
        try {
            return response()->json($this->serviceFlow->noShow($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function queue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
        ]);

        $queue = $this->queueService->getQueue(
            isset($validated['roomId']) ? (int) $validated['roomId'] : null
        )->map(function ($ticket) {
            $ticket->waitingMinutes = (int) now()->diffInMinutes($ticket->issued_at);

            return $ticket;
        });

        return response()->json($queue);
    }

    public function callNext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'roomId' => 'required|integer|exists:attendance_rooms,id',
        ]);

        try {
            $ticket = $this->queueService->callNext((int) auth()->id(), (int) $validated['roomId']);

            return response()->json($ticket);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sala não encontrada ou inativa.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function callSpecific(int $ticketId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'roomId' => 'required|integer|exists:attendance_rooms,id',
        ]);

        try {
            $ticket = $this->queueService->callSpecific($ticketId, (int) auth()->id(), (int) $validated['roomId']);

            return response()->json($ticket);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha ou sala não encontrada.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function serviceData(int $ticketId): JsonResponse
    {
        try {
            $ticket = $this->serviceFlow->getServiceData($ticketId);
            $pendingSummary = $this->pendingSummaryService->getByClient((int) $ticket->client_id);

            return response()->json([
                'ticket' => $ticket,
                'record' => $ticket->record,
                'pendingSummary' => $pendingSummary,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        }
    }

    public function serviceStart(int $ticketId): JsonResponse
    {
        try {
            return response()->json($this->serviceFlow->start($ticketId));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function serviceNotes(int $ticketId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            return response()->json($this->serviceFlow->saveNotes($ticketId, $validated['notes'] ?? null));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        }
    }

    public function serviceFinish(int $ticketId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            return response()->json($this->serviceFlow->finish($ticketId, $validated['notes'] ?? null));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Senha não encontrada.'], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function panelState(): JsonResponse
    {
        return response()->json($this->panelService->state());
    }

    public function rooms(): JsonResponse
    {
        return response()->json(
            \App\Models\AttendanceRoom::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'active'])
        );
    }

    public function roomsIndex(): JsonResponse
    {
        return response()->json(
            \App\Models\AttendanceRoom::query()
                ->orderByDesc('id')
                ->get(['id', 'name', 'description', 'active', 'created_at', 'updated_at'])
        );
    }

    public function roomsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:attendance_rooms,name',
            'description' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $room = \App\Models\AttendanceRoom::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        AuditService::record('ATTENDANCE_ROOM_CREATED', $room, null, $room->toArray());

        return response()->json($room, 201);
    }

    public function roomsShow(int $id): JsonResponse
    {
        try {
            $room = \App\Models\AttendanceRoom::query()->findOrFail($id);

            return response()->json($room);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sala não encontrada.'], 404);
        }
    }

    public function roomsUpdate(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:attendance_rooms,name,'.$id,
            'description' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        try {
            $room = \App\Models\AttendanceRoom::query()->findOrFail($id);
            $old = $room->toArray();
            $room->update($validated);
            AuditService::record('ATTENDANCE_ROOM_UPDATED', $room, $old, $room->toArray());

            return response()->json($room);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sala não encontrada.'], 404);
        }
    }

    public function roomsInactivate(int $id): JsonResponse
    {
        try {
            $room = \App\Models\AttendanceRoom::query()->findOrFail($id);
            $old = $room->toArray();
            $room->update(['active' => false]);
            AuditService::record('ATTENDANCE_ROOM_INACTIVATED', $room, $old, $room->toArray());

            return response()->json($room);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sala não encontrada.'], 404);
        }
    }

    public function roomsDestroy(int $id): JsonResponse
    {
        try {
            $room = \App\Models\AttendanceRoom::query()->findOrFail($id);
            AuditService::record('ATTENDANCE_ROOM_DELETED', $room, $room->toArray(), null);
            $room->delete();

            return response()->json(['message' => 'Sala removida com sucesso.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sala não encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sala vinculada a atendimentos não pode ser removida.'], 422);
        }
    }
}
