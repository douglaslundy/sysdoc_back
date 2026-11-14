<?php

namespace App\Http\Requests;

class IndexTripScheduleRequest extends TripScheduleRequest
{
    public function rules(): array
    {
        return ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'];
    }
}
