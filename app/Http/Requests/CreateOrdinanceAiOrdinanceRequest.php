<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrdinanceAiOrdinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:normativa,ordinatoria'],
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'legal_basis' => ['nullable', 'string'],
            'signatory_name' => ['required', 'string', 'max:150'],
            'signatory_role' => ['nullable', 'string', 'max:150'],
            'additional_instructions' => ['nullable', 'string'],
        ];
    }
}
