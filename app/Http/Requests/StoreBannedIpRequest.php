<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
