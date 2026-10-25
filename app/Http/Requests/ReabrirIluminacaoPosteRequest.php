<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReabrirIluminacaoPosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile === 'admin';
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
