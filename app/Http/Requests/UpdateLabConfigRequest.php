<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class UpdateLabConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'secretaria_id' => \App\Support\EscopoAlmoxarifado::regraCampo(),
            'email_habilitado' => 'boolean',
            'nome_estabelecimento' => 'nullable|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'endereco_rua' => 'nullable|string|max:255',
            'endereco_numero' => 'nullable|string|max:20',
            'endereco_bairro' => 'nullable|string|max:100',
            'endereco_cep' => 'nullable|string|max:8',
            'telefone' => 'nullable|string|max:20',
            'cnpj' => 'nullable|string|max:14',
            'email_lab' => 'nullable|email|max:255',
            'rodape1' => 'nullable|string',
            'rodape2' => 'nullable|string',
        ];

        if (Schema::hasColumn('lab_configs', 'imprimir_rascunho_exame')) {
            $rules['imprimir_rascunho_exame'] = 'boolean';
        }

        return $rules;
    }
}
