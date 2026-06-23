<?php

namespace Database\Seeders;

use App\Models\ProtocolConfig;
use App\Models\ProtocolOrganizationalUnit;
use Illuminate\Database\Seeder;

class ProtocolCatalogSeeder extends Seeder
{
    public function run(): void
    {
        ProtocolConfig::current();

        $secretaria = ProtocolOrganizationalUnit::firstOrCreate(
            ['nome' => 'Secretaria de Saúde', 'parent_id' => null],
            ['tipo' => 'secretaria', 'codigo' => 'SMS', 'descricao' => null, 'ativo' => true]
        );

        $departamento = ProtocolOrganizationalUnit::firstOrCreate(
            ['nome' => 'Departamento de Protocolo', 'parent_id' => $secretaria->id],
            ['tipo' => 'departamento', 'codigo' => 'PROT', 'descricao' => 'Fila central de protocolos', 'ativo' => true]
        );

        ProtocolOrganizationalUnit::firstOrCreate(
            ['nome' => 'Subdepartamento de Atendimento', 'parent_id' => $departamento->id],
            ['tipo' => 'subdepartamento', 'codigo' => 'ATD', 'descricao' => 'Atendimento e triagem', 'ativo' => true]
        );
    }
}
