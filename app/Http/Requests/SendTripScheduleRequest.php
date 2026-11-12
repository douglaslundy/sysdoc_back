<?php

namespace App\Http\Requests;

class SendTripScheduleRequest extends TripScheduleRequest
{
    public function rules(): array
    {
        return [
            'entry_ids' => 'required|array|min:1|max:100',
            'entry_ids.*' => 'integer',
            'trip_id' => 'nullable|integer|exists:trips,id',
            'new_trip' => 'nullable|array',
            'new_trip.route_id' => 'required_with:new_trip|integer|exists:routes,id',
            'new_trip.departure_date' => 'required_with:new_trip|date_format:Y-m-d',
            'new_trip.departure_time' => 'nullable|date_format:H:i',
            'new_trip.vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'new_trip.driver_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
