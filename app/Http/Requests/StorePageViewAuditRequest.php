<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageViewAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'path'    => ['required', 'string', 'max:255'],
            'label'   => ['nullable', 'string', 'max:100'],
            'filtros' => ['nullable', 'array'],
        ];
    }
}
