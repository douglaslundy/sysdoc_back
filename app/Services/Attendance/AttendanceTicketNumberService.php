<?php

namespace App\Services\Attendance;

use App\Models\AttendanceTicket;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AttendanceTicketNumberService
{
    public function nextNumberForDate(CarbonInterface $date): int
    {
        return DB::transaction(function () use ($date) {
            $last = AttendanceTicket::query()
                ->whereDate('sequence_date', $date->toDateString())
                ->lockForUpdate()
                ->max('number');

            return ((int) $last) + 1;
        });
    }

    public function buildDisplayCode(int $number, string $prefix = 'A'): string
    {
        return strtoupper($prefix) . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}

