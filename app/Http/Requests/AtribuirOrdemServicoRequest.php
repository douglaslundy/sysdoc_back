<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtribuirOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['tecnico_id' => ['required', 'integer', Rule::exists('users', 'id')->where('profile', 'tecnico')->where('active', true)]];
    }
}
