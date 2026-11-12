<?php

namespace App\Http\Requests;

use App\Models\TripScheduleEntry;
use Illuminate\Validation\Rule;

class UpdateTripScheduleRequest extends TripScheduleRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        // 404 do registro vem antes da validação, como no controller original.
        TripScheduleEntry::findOrFail($this->route('id'));

        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => 'sometimes|date_format:Y-m-d',
            'phone' => 'nullable|string|max:20',
            'departure_location' => 'nullable|string|max:50',
            'destination_location' => 'nullable|string|max:50',
            'time' => 'nullable|date_format:H:i',
            'status' => ['sometimes', Rule::in(['agendado', 'confirmado', 'cancelado', 'concluido'])],
            'obs' => 'nullable|string|max:300',
        ];
    }
}
