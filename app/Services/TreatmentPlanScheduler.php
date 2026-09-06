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
        $weekdays = array_map('intval', $weekdays);
        $cursor = ($from ?? now())->copy()->startOfDay();
        $dates = [];
        $guard = $totalSessions * 7 + 7; // hard cap: at most 7 days por sessão, mais uma semana de folga

        while (count($dates) < $totalSessions && $guard-- > 0) {
            if (in_array($cursor->dayOfWeekIso, $weekdays, true)) {
                $dates[] = $cursor->copy();
            }
            $cursor = $cursor->copy()->addDay();
        }

        if (count($dates) < $totalSessions) {
            throw new \InvalidArgumentException('Não foi possível distribuir as sessões com os dias da semana informados.');
        }

        return $dates;
    }
}
