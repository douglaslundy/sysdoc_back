<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLetterAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_user' => ['required'],
            'sender' => ['required', 'string'],
            'recipient' => ['required', 'string'],
            'subject_matter' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'wishes' => ['nullable', 'string'],
            'treatment_pronoun' => ['nullable', 'string'],
        ];
    }
}
