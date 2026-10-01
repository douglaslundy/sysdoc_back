<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clientId' => 'required|integer|exists:clients,id',
            'prefix' => 'nullable|string|max:3',
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
        ];
    }
}
