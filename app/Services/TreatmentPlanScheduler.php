<?php

namespace App\Services;

use Carbon\Carbon;

class TreatmentPlanScheduler
{
    /**
     * @param  array<int>  $weekdays  1 (segunda) a 7 (domingo), formato Carbon::dayOfWeekIso
     * @return array<\Carbon\Carbon>
     */
    public function distributeDates(array $weekdays, int $totalSessions, ?Carbon $from = null): array
    {
        $cursor = ($from ?? now())->copy()->startOfDay();
        $dates = [];

        while (count($dates) < $totalSessions) {
            if (in_array($cursor->dayOfWeekIso, $weekdays, true)) {
                $dates[] = $cursor->copy();
            }
            $cursor = $cursor->copy()->addDay();
        }

        return $dates;
    }
}
