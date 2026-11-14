<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlmoxarifadoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        // Catálogos são por secretaria: os iniciais ficam na Saúde (explícito, seeder roda fora de requisição).
        $sec = \App\Models\Secretaria::saudeId();

        $categories = [
            'Material de Consumo',
            'Material Permanente',
            'Higiene e Limpeza',
            'Escritório',
            'Expediente',
            'Alimentação',
            'Informática',
            'Elétrico e Eletrônico',
        ];

        foreach ($categories as $nome) {
            DB::table('almoxarifado_categorias')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $nome],
                ['observacoes' => null, 'ativo' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $species = [
            'Consumo',
            'Permanente',
            'Reagente',
            'Descartável',
            'Reposição',
        ];

        foreach ($species as $nome) {
            DB::table('almoxarifado_especies')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $nome],
                ['observacoes' => null, 'ativo' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $units = [
            ['nome' => 'Unidade', 'sigla' => 'UN'],
            ['nome' => 'Caixa', 'sigla' => 'CX'],
            ['nome' => 'Pacote', 'sigla' => 'PCT'],
            ['nome' => 'Kit', 'sigla' => 'KIT'],
            ['nome' => 'Frasco', 'sigla' => 'FR'],
            ['nome' => 'Litro', 'sigla' => 'L'],
            ['nome' => 'Quilograma', 'sigla' => 'KG'],
            ['nome' => 'Metro', 'sigla' => 'M'],
        ];

        foreach ($units as $unit) {
            DB::table('almoxarifado_unidades_medida')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $unit['nome']],
                ['sigla' => $unit['sigla'], 'observacoes' => null, 'ativo' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $secretarias = [
            ['nome' => 'Secretaria de Saúde', 'sigla' => 'SMS'],
            ['nome' => 'Secretaria de Educação', 'sigla' => 'SME'],
            ['nome' => 'Secretaria de Administração', 'sigla' => 'SMA'],
            ['nome' => 'Secretaria de Obras', 'sigla' => 'SMO'],
            ['nome' => 'Secretaria de Assistência Social', 'sigla' => 'SMAS'],
        ];

        foreach ($secretarias as $secretaria) {
            DB::table('almoxarifado_secretarias')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $secretaria['nome']],
                [
                    'sigla' => $secretaria['sigla'],
                    'responsavel' => null,
                    'contato' => null,
                    'observacoes' => null,
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $fornecedores = [
            ['nome' => 'Fornecedor Institucional', 'documento' => null],
            ['nome' => 'Fornecedor Local', 'documento' => null],
            ['nome' => 'Fornecedor Regional', 'documento' => null],
        ];

        foreach ($fornecedores as $fornecedor) {
            DB::table('almoxarifado_fornecedores')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $fornecedor['nome']],
                [
                    'documento' => $fornecedor['documento'],
                    'telefone' => null,
                    'email' => null,
                    'contato' => null,
                    'endereco' => null,
                    'observacoes' => null,
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $localizacoes = [
            ['nome' => 'Almoxarifado Central', 'almoxarifado' => 'Central'],
            ['nome' => 'Depósito Secundário', 'almoxarifado' => 'Central'],
            ['nome' => 'Farmácia Interna', 'almoxarifado' => 'Saúde'],
        ];

        foreach ($localizacoes as $localizacao) {
            DB::table('almoxarifado_localizacoes')->updateOrInsert(
                ['secretaria_id' => $sec, 'nome' => $localizacao['nome']],
                [
                    'almoxarifado' => $localizacao['almoxarifado'],
                    'sala' => null,
                    'corredor' => null,
                    'estante' => null,
                    'prateleira' => null,
                    'gaveta' => null,
                    'caixa' => null,
                    'posicao' => null,
                    'observacoes' => null,
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
