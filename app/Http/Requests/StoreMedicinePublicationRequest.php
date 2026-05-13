<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Validation\Rule;

class StoreMedicinePublicationRequest extends BaseApiFormRequest
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
            'reference_type' => ['required', Rule::in(['daily', 'monthly'])],
            'reference_id' => ['required', 'integer', 'min:1'],
            'channel' => ['required', Rule::in(['site', 'panel', 'instagram', 'facebook', 'other'])],
            'status' => ['required', Rule::in(['pending', 'published', 'failed'])],
            'payload_summary' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_type.required' => 'O tipo de referência é obrigatório.',
            'reference_type.in' => 'O tipo de referência deve ser "daily" ou "monthly".',
            'reference_id.required' => 'O identificador da referência é obrigatório.',
            'reference_id.integer' => 'O identificador da referência deve ser um número inteiro.',
            'reference_id.min' => 'O identificador da referência deve ser maior que zero.',
            'channel.required' => 'O canal de publicação é obrigatório.',
            'channel.in' => 'O canal deve ser site, panel, instagram, facebook ou other.',
            'status.required' => 'O status da publicação é obrigatório.',
            'status.in' => 'O status deve ser pending, published ou failed.',
            'payload_summary.array' => 'O resumo do payload deve ser um objeto JSON.',
            'published_at.date' => 'A data de publicação deve estar em formato válido.',
        ];
    }
}
