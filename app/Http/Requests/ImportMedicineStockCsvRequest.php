<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class ImportMedicineStockCsvRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, ['/pharmacy/daily-status', '/pharmacy/medicines']);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo CSV é obrigatório.',
            'file.file' => 'O arquivo enviado é inválido.',
            'file.mimes' => 'Envie um arquivo no formato CSV.',
            'file.max' => 'O arquivo deve ter no máximo 10MB.',
        ];
    }
}
