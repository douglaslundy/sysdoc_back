<?php

namespace App\Http\Controllers;

use App\Models\QueueTreatmentSession;
use App\Services\Authorization\SpecialityPermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueTreatmentSessionController extends Controller
{
    public function reschedule(Request $request, QueueTreatmentSession $session)
    {
        $data = $request->validate([
            'new_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $plan = $session->plan;
        $user = $request->user();

        if (! app(SpecialityPermissionService::class)->canEdit($user, $plan->speciality_id)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $oldDate = Carbon::parse($session->scheduled_date);
        $newDate = Carbon::parse($data['new_date']);
        $delayDays = $oldDate->diffInDays($newDate, false);

        DB::transaction(function () use ($session, $plan, $newDate, $delayDays, $data, $oldDate) {
            $session->update([
                'original_scheduled_date' => $session->original_scheduled_date ?? $oldDate->toDateString(),
                'scheduled_date' => $newDate->toDateString(),
                'status' => 'rescheduled_pending',
                'reschedule_reason' => $data['reason'],
            ]);

            if ($delayDays > 0) {
                $plan->update([
                    'expected_end_at' => Carbon::parse($plan->expected_end_at)->addDays($delayDays)->toDateString(),
                ]);
            }
        });

        $plan->refresh()->load('sessions');

        return response()->json(QueueTreatmentPlanController::formatPlan($plan));
    }
}
