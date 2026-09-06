<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\QueueTreatmentPlan;
use App\Models\QueueTreatmentSession;
use App\Services\Authorization\SpecialityPermissionService;
use App\Services\TreatmentPlanScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueTreatmentPlanController extends Controller
{
    public function preview(Request $request)
    {
        $data = $request->validate([
            'speciality_id' => ['required', 'integer', 'exists:specialities,id'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $user = $request->user();
        if (! app(SpecialityPermissionService::class)->canInsert($user, (int) $data['speciality_id'])) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $dates = (new TreatmentPlanScheduler())->distributeDates($data['weekdays'], $data['total_sessions']);

        return response()->json([
            'dates' => array_map(fn ($date) => $date->toDateString(), $dates),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'queue_id' => ['required', 'integer', 'exists:queue,id'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $queue = Queue::with('speciality')->findOrFail($data['queue_id']);

        $user = $request->user();
        if (! app(SpecialityPermissionService::class)->canInsert($user, $queue->id_specialities)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        if (! $queue->speciality || ! $queue->speciality->allows_session_scheduling) {
            return response()->json([
                'message' => 'Esta especialidade não permite agendamento por sessões.',
            ], 422);
        }

        $dates = (new TreatmentPlanScheduler())->distributeDates($data['weekdays'], $data['total_sessions']);

        $plan = DB::transaction(function () use ($queue, $data, $dates, $user) {
            $plan = QueueTreatmentPlan::create([
                'queue_id' => $queue->id,
                'speciality_id' => $queue->id_specialities,
                'client_id' => $queue->id_client,
                'created_by_user_id' => $user->id,
                'total_sessions' => $data['total_sessions'],
                'weekdays' => $data['weekdays'],
                'started_at' => $dates[0]->toDateString(),
                'expected_end_at' => end($dates)->toDateString(),
                'status' => 'active',
            ]);

            foreach ($dates as $date) {
                QueueTreatmentSession::create([
                    'treatment_plan_id' => $plan->id,
                    'scheduled_date' => $date->toDateString(),
                    'status' => 'pending',
                ]);
            }

            $queue->update(['done' => true, 'date_of_realized' => now()->toDateString()]);

            return $plan;
        });

        $plan->load('sessions');

        return response()->json($this->formatPlan($plan), 201);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'active');
        $user = $request->user();
        $service = app(SpecialityPermissionService::class);

        $plans = QueueTreatmentPlan::query()
            ->with(['sessions', 'client:id,name', 'speciality:id,name'])
            ->where('status', $status)
            ->orderBy('expected_end_at')
            ->get()
            ->filter(fn ($plan) => $service->canView($user, $plan->speciality_id))
            ->values();

        return response()->json($plans->map(function ($plan) {
            return array_merge(self::formatPlan($plan), [
                'client_name' => $plan->client?->name,
                'speciality_name' => $plan->speciality?->name,
            ]);
        }));
    }

    public function show(Request $request, QueueTreatmentPlan $plan)
    {
        $user = $request->user();
        if (! app(SpecialityPermissionService::class)->canView($user, $plan->speciality_id)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $plan->load(['sessions', 'client:id,name', 'speciality:id,name']);

        return response()->json(array_merge(self::formatPlan($plan), [
            'client_name' => $plan->client?->name,
            'speciality_name' => $plan->speciality?->name,
        ]));
    }

    public function forQueue(Request $request, $queueId)
    {
        $queue = Queue::findOrFail($queueId);
        $user = $request->user();

        if (! app(SpecialityPermissionService::class)->canView($user, $queue->id_specialities)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $plan = QueueTreatmentPlan::query()
            ->with(['sessions', 'client:id,name', 'speciality:id,name'])
            ->where('queue_id', $queueId)
            ->latest('id')
            ->first();

        if (! $plan) {
            return response()->json(['plan' => null]);
        }

        return response()->json(array_merge(self::formatPlan($plan), [
            'client_name' => $plan->client?->name,
            'speciality_name' => $plan->speciality?->name,
        ]));
    }

    public static function formatPlan(QueueTreatmentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'queue_id' => $plan->queue_id,
            'speciality_id' => $plan->speciality_id,
            'client_id' => $plan->client_id,
            'total_sessions' => $plan->total_sessions,
            'weekdays' => $plan->weekdays,
            'started_at' => optional($plan->started_at)->toDateString(),
            'expected_end_at' => optional($plan->expected_end_at)->toDateString(),
            'status' => $plan->status,
            'cancelled_reason' => $plan->cancelled_reason,
            'sessions' => $plan->sessions->map(fn ($session) => [
                'id' => $session->id,
                'scheduled_date' => optional($session->scheduled_date)->toDateString(),
                'original_scheduled_date' => optional($session->original_scheduled_date)->toDateString(),
                'status' => $session->status,
                'reschedule_reason' => $session->reschedule_reason,
            ])->values(),
        ];
    }
}
