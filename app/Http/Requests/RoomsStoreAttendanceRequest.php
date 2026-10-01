<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomsStoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:attendance_rooms,name',
            'description' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ];
    }
}
