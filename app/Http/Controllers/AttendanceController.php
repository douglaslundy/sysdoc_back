<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendancePanelService;
use App\Services\Attendance\AttendancePendingSummaryService;
use App\Services\Attendance\AttendanceQueueService;
use App\Services\Attendance\AttendanceServiceFlowService;
use App\Services\Attendance\AttendanceTicketService;
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
        ]);

        $ticket = $this->ticketService->issueTicket(
            (int) $validated['clientId'],
            auth()->id(),
            $validated['prefix'] ?? 'A'
        );

        return response()->json($ticket, 201);
    }

    public function listTickets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:aguardando,chamada,em_atendimento,finalizada,cancelada,nao_compareceu',
            'clientId' => 'nullable|integer|exists:clients,id',
            'issuedFrom' => 'nullable|date',
            'issuedTo' => 'nullable|date',
        ]);

        return response()->json($this->ticketService->listTickets($validated));
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

    public function queue(): JsonResponse
    {
        $queue = $this->queueService->getQueue()->map(function ($ticket) {
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
}
