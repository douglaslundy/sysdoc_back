<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class ListMedicinePublicationsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, [
                '/pharmacy/daily-status',
                '/pharmacy/monthly-acquisitions',
            ]);
    }

    public function rules(): array
    {
        return [
            'reference_type' => ['nullable', Rule::in(['daily', 'monthly'])],
            'channel' => ['nullable', Rule::in(['site', 'panel', 'instagram', 'facebook', 'other'])],
            'status' => ['nullable', Rule::in(['pending', 'published', 'failed'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_type.in' => 'O tipo de referência deve ser "daily" ou "monthly".',
            'channel.in' => 'O canal deve ser site, panel, instagram, facebook ou other.',
            'status.in' => 'O status deve ser pending, published ou failed.',
            'per_page.integer' => 'A paginação deve ser um número inteiro.',
            'per_page.min' => 'A paginação mínima é 1.',
            'per_page.max' => 'A paginação máxima é 200.',
        ];
    }
}
