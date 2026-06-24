<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['codigo' => 'oficio', 'nome' => 'Ofício', 'descricao' => 'Comunicação administrativa formal.', 'ordem' => 1],
            ['codigo' => 'memorando', 'nome' => 'Memorando', 'descricao' => 'Comunicação interna entre setores.', 'ordem' => 2],
            ['codigo' => 'portaria', 'nome' => 'Portaria', 'descricao' => 'Ato administrativo normativo.', 'ordem' => 3],
            ['codigo' => 'ata', 'nome' => 'Ata', 'descricao' => 'Registro de reunião ou sessão.', 'ordem' => 4],
            ['codigo' => 'relatorio', 'nome' => 'Relatório', 'descricao' => 'Documento técnico ou gerencial.', 'ordem' => 5],
            ['codigo' => 'despacho', 'nome' => 'Despacho', 'descricao' => 'Decisão ou encaminhamento administrativo.', 'ordem' => 6],
            ['codigo' => 'requerimento', 'nome' => 'Requerimento', 'descricao' => 'Solicitação formal.', 'ordem' => 7],
            ['codigo' => 'contrato', 'nome' => 'Contrato', 'descricao' => 'Instrumento contratual.', 'ordem' => 8],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->updateOrInsert(
                ['codigo' => $type['codigo']],
                [
                    'nome' => $type['nome'],
                    'descricao' => $type['descricao'],
                    'ordem' => $type['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
