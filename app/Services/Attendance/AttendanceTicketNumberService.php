<?php

namespace App\Services\Attendance;

use App\Models\AttendanceTicket;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AttendanceTicketNumberService
{
    public function nextNumberForDate(CarbonInterface $date, ?int $secretariaId = null): int
    {
        return DB::transaction(function () use ($date, $secretariaId) {
            $last = AttendanceTicket::query()
                ->when($secretariaId, fn ($q) => $q->where('secretaria_id', $secretariaId))
                ->whereDate('sequence_date', $date->toDateString())
                ->lockForUpdate()
                ->max('number');

            return ((int) $last) + 1;
        });
    }

    public function buildDisplayCode(int $number, string $prefix = 'A'): string
    {
        return strtoupper($prefix).str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
