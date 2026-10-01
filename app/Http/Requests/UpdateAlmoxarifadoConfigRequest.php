<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlmoxarifadoConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permitir_saida_sem_saldo' => 'boolean',
            'permitir_transferencia_entre_secretarias' => 'boolean',
            'exigir_justificativa_saida' => 'boolean',
            'exigir_localizacao_produto' => 'boolean',
            'notificar_estoque_minimo' => 'boolean',
            'estoque_minimo_alerta_percentual' => 'nullable|integer|min:1|max:100',
            'permite_produto_sem_validade' => 'boolean',
            'observacoes' => 'nullable|string',
        ];
    }
}
