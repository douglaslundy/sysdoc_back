<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlmoxarifadoRequisicaoFakeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $userId = DB::table('users')->where('active', true)->orderBy('id')->value('id');
            $secretarias = DB::table('almoxarifado_secretarias')
                ->where('ativo', true)
                ->orderBy('id')
                ->get();

            if ($secretarias->isEmpty()) {
                $this->call(AlmoxarifadoCatalogSeeder::class);
                $secretarias = DB::table('almoxarifado_secretarias')
                    ->where('ativo', true)
                    ->orderBy('id')
                    ->get();
            }

            $produtos = [
                ['codigo' => 'FAKE-PAPEL-A4', 'nome' => 'Papel A4 - Teste'],
                ['codigo' => 'FAKE-CANETA-AZUL', 'nome' => 'Caneta Azul - Teste'],
                ['codigo' => 'FAKE-PASTA-ARQUIVO', 'nome' => 'Pasta para Arquivo - Teste'],
                ['codigo' => 'FAKE-ALCOOL-70', 'nome' => 'Álcool 70% - Teste'],
                ['codigo' => 'FAKE-LUVA-PROC', 'nome' => 'Luva de Procedimento - Teste'],
            ];

            $produtoIds = [];
            foreach ($produtos as $produto) {
                DB::table('almoxarifado_produtos')->updateOrInsert(
                    ['codigo_interno' => $produto['codigo']],
                    [
                        'nome' => $produto['nome'],
                        'descricao' => 'Produto fictício para testes das rotinas do almoxarifado.',
                        'estoque_minimo' => 10,
                        'estoque_maximo' => 200,
                        'ativo' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
                $produtoIds[] = DB::table('almoxarifado_produtos')
                    ->where('codigo_interno', $produto['codigo'])
                    ->value('id');
            }

            foreach ($secretarias as $secretaria) {
                foreach ($produtoIds as $produtoId) {
                    DB::table('almoxarifado_estoques')->updateOrInsert(
                        [
                            'almoxarifado_produto_id' => $produtoId,
                            'almoxarifado_secretaria_id' => $secretaria->id,
                        ],
                        [
                            'quantidade_disponivel' => 100,
                            'quantidade_reservada' => 0,
                            'quantidade_em_separacao' => 0,
                            'quantidade_entregue' => 0,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }

            for ($index = 1; $index <= 10; $index++) {
                $numero = sprintf('REQ-FAKE-%03d', $index);
                $secretaria = $secretarias[($index - 1) % $secretarias->count()];

                DB::table('almoxarifado_requisicoes')->updateOrInsert(
                    ['numero' => $numero],
                    [
                        'almoxarifado_secretaria_id' => $secretaria->id,
                        'solicitante' => "Solicitante Teste {$index}",
                        'data_solicitacao' => now()->subDays(10 - $index)->toDateString(),
                        'status' => 'recebida',
                        'justificativa' => "Requisição fictícia {$index} para validar o fluxo do almoxarifado.",
                        'observacoes' => 'Registro criado pelo AlmoxarifadoRequisicaoFakeSeeder.',
                        'usuario_responsavel_id' => $userId,
                        'data_atendimento' => null,
                        'data_entrega' => null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $requisicaoId = DB::table('almoxarifado_requisicoes')
                    ->where('numero', $numero)
                    ->value('id');
                $itens = [
                    $produtoIds[($index - 1) % count($produtoIds)] => $index + 1,
                    $produtoIds[$index % count($produtoIds)] => $index + 2,
                ];

                foreach ($itens as $produtoId => $quantidade) {
                    DB::table('almoxarifado_requisicao_itens')->updateOrInsert(
                        [
                            'almoxarifado_requisicao_id' => $requisicaoId,
                            'almoxarifado_produto_id' => $produtoId,
                        ],
                        [
                            'quantidade_solicitada' => $quantidade,
                            'quantidade_atendida' => 0,
                            'quantidade_entregue' => 0,
                            'observacao' => 'Item fictício para teste.',
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }

                DB::table('almoxarifado_requisicao_status_historicos')->updateOrInsert(
                    [
                        'almoxarifado_requisicao_id' => $requisicaoId,
                        'status_anterior' => null,
                        'novo_status' => 'recebida',
                    ],
                    [
                        'observacao' => 'Requisição fictícia criada para testes.',
                        'user_id' => $userId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }
}
