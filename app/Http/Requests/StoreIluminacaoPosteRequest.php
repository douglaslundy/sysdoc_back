<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreIluminacaoPosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/iluminacao/cadastro');
    }

    public function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'ponto_referencia' => ['required', 'string', 'min:3', 'max:160'],
            'logradouro' => ['required', 'string', 'max:160'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'precisao_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'fotos' => ['sometimes', 'array', 'max:4'],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Só o servidor define estes campos.
        $this->request->remove('status');
        $this->request->remove('cadastrado_por');
        $this->request->remove('protocolo');
        $this->request->remove('active');
    }
}
