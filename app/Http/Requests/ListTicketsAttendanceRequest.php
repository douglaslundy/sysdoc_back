<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTicketsAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:aguardando,chamada,em_atendimento,finalizada,cancelada,nao_compareceu',
            'clientId' => 'nullable|integer|exists:clients,id',
            'roomId' => 'nullable|integer|exists:attendance_rooms,id',
            'assignedUserId' => 'nullable|integer|exists:users,id',
            'issuedFrom' => 'nullable|date',
            'issuedTo' => 'nullable|date',
            'serviceFrom' => 'nullable|date',
            'serviceTo' => 'nullable|date',
        ];
    }
}
