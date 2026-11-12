<?php

namespace App\Http\Requests;

use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class CallSpecificAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roomId' => ['required', 'integer', EscopoAlmoxarifado::existeNaAtiva('attendance_rooms')],
        ];
    }
}
