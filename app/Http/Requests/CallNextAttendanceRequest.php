<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallNextAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roomId' => 'required|integer|exists:attendance_rooms,id',
        ];
    }
}
