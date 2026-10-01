<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PresenceChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state' => ['required', Rule::in(['online', 'away', 'offline'])],
            'path' => ['nullable', 'string', 'max:255'],
            'connection_id' => ['required', 'uuid'],
        ];
    }
}
