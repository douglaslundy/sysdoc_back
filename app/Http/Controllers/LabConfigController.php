<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLabConfigRequest;
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

    public function update(UpdateLabConfigRequest $request)
    {
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
