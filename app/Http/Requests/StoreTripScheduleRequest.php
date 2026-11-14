<?php

namespace App\Http\Requests;

use App\Models\Secretaria;

class StoreTripScheduleRequest extends TripScheduleRequest
{
    public function rules(): array
    {
        return [
            'scheduled_date' => 'required_without:companion_of|nullable|date_format:Y-m-d',
            'client_id' => 'required|integer|exists:clients,id',
            'companion_of' => 'nullable|integer|exists:trip_schedule_entries,id',
            'phone' => 'nullable|string|max:20',
            'departure_location' => 'nullable|string|max:50',
            'destination_location' => 'nullable|string|max:50',
            'time' => 'nullable|date_format:H:i',
            'obs' => 'nullable|string|max:300',
            'secretaria_id' => Secretaria::regraId(),
            'companions' => 'nullable|array|max:10',
            'companions.*.client_id' => 'required|integer|exists:clients,id',
            'companions.*.phone' => 'nullable|string|max:20',
        ];
    }
}
