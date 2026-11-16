<?php

namespace App\Http\Requests;

use App\Models\UserAtalho;
use Illuminate\Foundation\Http\FormRequest;

class ReordenarUserAtalhosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.UserAtalho::MAXIMO],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}
