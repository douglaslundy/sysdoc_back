<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivo' => 'required|file|max:30720|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'descricao' => 'nullable|string|max:255',
        ];
    }
}
