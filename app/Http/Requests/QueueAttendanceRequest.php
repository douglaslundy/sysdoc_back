<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
        ];
    }
}
