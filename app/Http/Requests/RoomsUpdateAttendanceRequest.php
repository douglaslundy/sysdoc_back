<?php

namespace App\Http\Requests;

use App\Models\AttendanceRoom;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class RoomsUpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');
        // 404 se a sala for de outra secretaria; o nome é único só dentro da secretaria da sala.
        $sid = EscopoAlmoxarifado::alvo(AttendanceRoom::class, $id);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', EscopoAlmoxarifado::unico('attendance_rooms', 'name', $sid)->ignore($id)],
            'description' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ];
    }
}
