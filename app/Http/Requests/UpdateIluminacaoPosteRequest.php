<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIluminacaoPosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/iluminacao/cadastro');
    }

    public function rules(): array
    {
        return [
            'ponto_referencia' => ['sometimes', 'required', 'string', 'min:3', 'max:160'],
            'logradouro' => ['sometimes', 'required', 'string', 'max:160'],
            'numero' => ['sometimes', 'nullable', 'string', 'max:20'],
            'bairro' => ['sometimes', 'required', 'string', 'max:100'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'precisao_m' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }
}
