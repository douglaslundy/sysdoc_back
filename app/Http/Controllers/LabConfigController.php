<?php

namespace App\Http\Controllers;

use App\Models\LabConfig;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LabConfigController extends Controller
{
    public function show()
    {
        return response()->json(LabConfig::get());
    }

    public function update(Request $request)
    {
        $request->validate([
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
        ]);

        $config = LabConfig::get();
        $old = $config->toArray();

        $fields = [
            'email_habilitado',
            'nome_estabelecimento', 'razao_social',
            'endereco_rua', 'endereco_numero', 'endereco_bairro', 'endereco_cep',
            'telefone', 'cnpj', 'email_lab',
            'rodape1', 'rodape2',
        ];

        if (Schema::hasColumn('lab_configs', 'imprimir_rascunho_exame')) {
            $request->validate([
                'imprimir_rascunho_exame' => 'boolean',
            ]);
            $fields[] = 'imprimir_rascunho_exame';
        }

        $config->update($request->only($fields));

        AuditService::record('UPDATE', $config, $old, $config->fresh()->toArray());

        return response()->json($config->fresh());
    }
}
