<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class ReplicateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/trips');
    }

    public function rules()
    {
        return [
            'dates' => 'required|array|min:1|max:60',
            'dates.*' => 'required|date_format:Y-m-d|after_or_equal:today',
        ];
    }

    public function messages()
    {
        return [
            'dates.required' => 'Selecione ao menos uma data.',
            'dates.array' => 'O campo de datas deve ser uma lista.',
            'dates.min' => 'Selecione ao menos uma data.',
            'dates.max' => 'Você pode replicar para no máximo 60 datas por vez.',
            'dates.*.required' => 'Uma das datas selecionadas está vazia.',
            'dates.*.date_format' => 'Cada data deve estar no formato AAAA-MM-DD.',
            'dates.*.after_or_equal' => 'Não é possível replicar a viagem para uma data passada.',
        ];
    }
}
