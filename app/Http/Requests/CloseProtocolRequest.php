<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'justificativa_encerramento' => 'required|string',
        ];
    }
}
