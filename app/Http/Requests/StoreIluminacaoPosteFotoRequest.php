<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreIluminacaoPosteFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PagePermissionService::class)->canAccess($user, '/iluminacao/cadastro');
    }

    public function rules(): array
    {
        return [
            'fotos' => ['required', 'array', 'min:1', 'max:4'],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
