<?php

namespace App\Http\Requests;

use App\Http\Controllers\ProtocolAlertController;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProtocolAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'modulo' => 'sometimes|required|string|max:80',
            'gatilho' => 'sometimes|required|string|max:80',
            'condicoes' => 'nullable|array',
            'condicoes.*' => ['string', Rule::in(ProtocolAlertController::CONDITIONS)],
            'canais' => 'required|array|min:1',
            'canais.*' => ['string', Rule::in(ProtocolAlertController::CHANNELS)],
            'destinatarios' => 'nullable|array',
            'destinatarios.*' => ['string', Rule::in(ProtocolAlertController::RECIPIENTS)],
            'template' => 'nullable|string',
            'ativo' => 'nullable|boolean',
            'frequencia' => 'nullable|string|max:60',
            'prevenir_duplicidade' => 'nullable|boolean',
        ];
    }
}
