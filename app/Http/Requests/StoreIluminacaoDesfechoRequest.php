<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreIluminacaoDesfechoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/iluminacao/servico');
    }

    public function rules(): array
    {
        return [
            'resultado' => ['required', 'in:concluido,nao_concluido,aguardando'],
            'motivo' => ['required_if:resultado,nao_concluido,aguardando', 'nullable', 'string', 'min:3', 'max:1000'],
            'fotos' => ['sometimes', 'array', 'max:4'],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
