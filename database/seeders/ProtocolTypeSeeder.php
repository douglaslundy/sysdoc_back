<?php

namespace Database\Seeders;

use App\Models\ProtocolType;
use Illuminate\Database\Seeder;

class ProtocolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['codigo' => 'administrativo', 'nome' => 'Administrativo', 'descricao' => 'Demandas administrativas internas.', 'ordem' => 1],
            ['codigo' => 'interno', 'nome' => 'Interno', 'descricao' => 'Protocolos restritos ao fluxo interno.', 'ordem' => 2],
            ['codigo' => 'externo', 'nome' => 'Externo', 'descricao' => 'Demandas recebidas de fora da unidade.', 'ordem' => 3],
            ['codigo' => 'oficio', 'nome' => 'Oficio', 'descricao' => 'Oficios e comunicacoes formais.', 'ordem' => 4],
            ['codigo' => 'memorando', 'nome' => 'Memorando', 'descricao' => 'Memorandos internos e intersetoriais.', 'ordem' => 5],
            ['codigo' => 'requerimento', 'nome' => 'Requerimento', 'descricao' => 'Solicitacoes formais de analise ou autorizacao.', 'ordem' => 6],
            ['codigo' => 'solicitacao', 'nome' => 'Solicitacao', 'descricao' => 'Pedidos gerais e demandas de servico.', 'ordem' => 7],
            ['codigo' => 'encaminhamento', 'nome' => 'Encaminhamento', 'descricao' => 'Movimentacoes para outras areas ou equipes.', 'ordem' => 8],
        ];

        foreach ($types as $type) {
            ProtocolType::query()->updateOrCreate(
                ['codigo' => $type['codigo']],
                [
                    'nome' => $type['nome'],
                    'descricao' => $type['descricao'],
                    'ordem' => $type['ordem'],
                    'ativo' => true,
                ]
            );
        }
    }
}
