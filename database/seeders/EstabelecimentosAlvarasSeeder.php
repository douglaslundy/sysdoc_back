<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstabelecimentosAlvarasSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('estabelecimentos')->exists()) {
            return;
        }

        $agora = now();

        // Linha 2
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Wagner Eduardo Bueno',
            'nome_estabelecimento' => 'Academia Evolution Fitness',
            'endereco'             => 'Rua do Comércio, 420',
            'cnaes'                => '9313-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-29',
            'vencimento_alvara'  => '2026-09-29',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 3
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Sandro Menezes de Oliveira',
            'nome_estabelecimento' => 'Academia Saúde Total',
            'endereco'             => 'Rua do Comércio, 215',
            'cnaes'                => '9313-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '19-08/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-15',
            'vencimento_alvara'  => '2026-09-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 4
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Marcelino Moreira Costa',
            'nome_estabelecimento' => 'Academia Vida Ativa',
            'endereco'             => 'Rua Direita, 110',
            'cnaes'                => '9313-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-29',
            'vencimento_alvara'  => '2026-09-29',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 5
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'A informar',
            'nome_estabelecimento' => 'Açaí e CIA',
            'endereco'             => 'Endereço a informar.',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 6
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Neimar Pereira Guedes',
            'nome_estabelecimento' => 'Açaí Sorvetes',
            'endereco'             => 'Rua Direita, 72 - Centro',
            'cnaes'                => '1053-8/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 7
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Celeno César de Sousa',
            'nome_estabelecimento' => 'Açougue Casa da Carne João e Maria',
            'endereco'             => 'Rua Doze de Outúbro, 78',
            'cnaes'                => '4722-9/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-06/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-06-05',
            'vencimento_alvara'  => '2026-06-05',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 8
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Dayvison Wallas Claudino',
            'nome_estabelecimento' => 'Adega DAYVISON WALLAS CLAUDINO (Distribuidora DJ)',
            'endereco'             => 'Rua Brasil, 08',
            'cnaes'                => '4723-7/00 4789-0/99 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 9
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Antônio Roberto de Andrade Junior',
            'nome_estabelecimento' => 'Adega do Juninho do Beto',
            'endereco'             => 'Rua Santa Catarina, 22',
            'cnaes'                => '5611-2/04 5320-2/02 4729-6/01 5611-2/03 5611-2/05',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 10
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Vinícius Eugênio Alvarenga de Melo',
            'nome_estabelecimento' => 'Adega House Beer',
            'endereco'             => 'Praça Padre João Lourenço Leite, 02',
            'cnaes'                => '4723-7/00 4729-6/01 4729-6/99 5611-2/05',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-08',
            'vencimento_alvara'  => '2026-10-08',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 11
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Bruna Loraine Rocha Assis',
            'nome_estabelecimento' => 'Ar Essence Espaço Integrado',
            'endereco'             => 'Avenida José Vilela da Costa, 980 - Cidade Nova',
            'cnaes'                => '8650-0/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-07/2025',
            'nivel_risco'        => '1',
            'data_alvara'        => '2025-07-11',
            'vencimento_alvara'  => null,
            'contato'            => null,
            'status'             => 'Não requerido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 12
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Querem Danielle Rocha Assis',
            'nome_estabelecimento' => 'Ar Essence Espaço Integrado',
            'endereco'             => 'Avenida José Vilela da Costa, 980 - Cidade Nova',
            'cnaes'                => '8650-0/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-11',
            'vencimento_alvara'  => '2026-07-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 13
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Odilon Rodrigues de Oliveira',
            'nome_estabelecimento' => 'Atividade de Estética',
            'endereco'             => 'Rua Paraíba, 370',
            'cnaes'                => '9602-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 14
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Larissa Bernardes de Oliveira',
            'nome_estabelecimento' => 'Bar Chalé da XV',
            'endereco'             => 'Avenida XV de Novembro, 1457',
            'cnaes'                => '5611-2/05 4712-1/00 4723-7/00 5611-2/03 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 15
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Rosilene Júlia Vilela',
            'nome_estabelecimento' => 'Bar da Rosy',
            'endereco'             => 'Rua Maranhão, 182',
            'cnaes'                => '5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-02/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-03-19',
            'vencimento_alvara'  => '2026-03-19',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 16
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Roberto Antônio de Lima',
            'nome_estabelecimento' => 'Bar do Baiano',
            'endereco'             => 'Rua Vanilton Vilela de Faria, 242',
            'cnaes'                => '5611-2/04 4723-7/00 4929-6/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 17
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Rosimeire Aparecida da Costa',
            'nome_estabelecimento' => 'Bar do Beto',
            'endereco'             => 'Rua do Comércio, 272',
            'cnaes'                => '5611-2/05',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 18
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'José Marcio dos Santos',
            'nome_estabelecimento' => 'Bar do José Marcio',
            'endereco'             => 'Rua Querubino Vilela Moscardini, 245',
            'cnaes'                => '5611-2/05 4723-7 4729-6/01 5611-2/04 4729-6/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 19
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Rone Marcos Alves',
            'nome_estabelecimento' => 'Bar do Rone',
            'endereco'             => 'Avenida José Vilela da Costa, 211',
            'cnaes'                => '5611-2/05 4723-7/00 4729-6/01 4729-6/99  5611-2/04 5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 20
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Marciel Antônio da Silva',
            'nome_estabelecimento' => 'BAR E PETISCARIA DO MARCIEL LTDA',
            'endereco'             => 'Rua Vanilton Vilela de Faria, 292',
            'cnaes'                => '5611-2/05 4723-7/00 5611-2/01 5611-2/03 5611-2/04 7319-0/99 8230-0/01 8230-0/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 21
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'João Paulo Lucarini Bueno',
            'nome_estabelecimento' => 'Bar Morada Caipira',
            'endereco'             => 'Rua Vanilton Vilela de Faria, 302',
            'cnaes'                => '4723-7/00 5620-1/04 4789-0/99 4729-6/01 4721-1/03 4729-6/99 5612-1/00 4724-5/00 4721-1/02 4772-5/00 4789-0/04 4789-0/05',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 22
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Luiza Montanine',
            'nome_estabelecimento' => 'Bar Recanto da Lagoa',
            'endereco'             => 'Rua Joaquim Marciano de Carvalho',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 23
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Irandi Patrocinio Leite',
            'nome_estabelecimento' => 'Barbearia do Pretinho',
            'endereco'             => 'Rua Boa Esperança, 540',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-02/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-13',
            'vencimento_alvara'  => '2026-02-13',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 24
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Guilherme Henrique Vilela Messias',
            'nome_estabelecimento' => 'Barbearia Gentleman\'s Barbershop',
            'endereco'             => 'Avenida XV de Novembro, 120',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-08/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-08-05',
            'vencimento_alvara'  => '2026-08-05',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 25
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Kelder de Lucas',
            'nome_estabelecimento' => 'Barbearia kerder Cortes',
            'endereco'             => 'Rua Sebastião Cardoso, 220 - Centro',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-07',
            'vencimento_alvara'  => '2026-07-07',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 26
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Matheus Oliveira Pereira',
            'nome_estabelecimento' => 'Barbearia Matheus',
            'endereco'             => 'Rua Padre Jose Zellis, 102',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-02/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-24',
            'vencimento_alvara'  => '2026-02-24',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 27
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Weverton Cesar de Oliveira',
            'nome_estabelecimento' => 'Barbearia Oliveira',
            'endereco'             => 'Rua Leopoldino Mendes, 141',
            'cnaes'                => '9602-5/01 4772-5/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '17-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-21',
            'vencimento_alvara'  => '2026-10-21',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 28
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Cristiane Barbosa Ferreira',
            'nome_estabelecimento' => 'BARBOSA FERREIRA LABORATORIO LTDA (Labcon)',
            'endereco'             => 'Avenida 15 de Novembro, 206',
            'cnaes'                => '8640-2/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-11/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-11-11',
            'vencimento_alvara'  => '2026-11-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 29
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Neymar Eventos',
            'nome_estabelecimento' => 'Barracas Motorock',
            'endereco'             => 'Praça Padre João Lourenço Leite',
            'cnaes'                => '8230-0/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 30
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Lucimar Aparecida Teixeira',
            'nome_estabelecimento' => 'Bona Quitanda',
            'endereco'             => 'Rua Herculino da Silva, 73',
            'cnaes'                => '1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '07-01/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-01-27',
            'vencimento_alvara'  => '2027-01-27',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 31
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Tereza Cristina Vilela Lemos',
            'nome_estabelecimento' => 'Bradesco S.A.',
            'endereco'             => 'Rua Direita, 173 - Centro',
            'cnaes'                => '6422-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 32
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Elias Alves dos Santos',
            'nome_estabelecimento' => 'Buteko da Praça',
            'endereco'             => 'Rua Doze de Outubro, 213',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 33
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ana Luiza Alves Teixeira',
            'nome_estabelecimento' => 'Cantinho do Açaí',
            'endereco'             => 'Rua do comércio, 586',
            'cnaes'                => '4729-6/99 5611-2/03 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 34
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Elaine Aparecida Pereira',
            'nome_estabelecimento' => 'Casa de Rações Amigo do Campo',
            'endereco'             => 'Rua Brasil, 298',
            'cnaes'                => '4789-0/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-02/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-02-07',
            'vencimento_alvara'  => '2026-02-07',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 35
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Lucineia Santos',
            'nome_estabelecimento' => 'Centro Dias (APAE)',
            'endereco'             => 'Rua Querubino Vilela Moscardini, 125',
            'cnaes'                => '9430-8',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-06/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-06-04',
            'vencimento_alvara'  => '2026-06-04',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 36
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'A informar',
            'nome_estabelecimento' => 'Clínica F&L Aliança LTDA',
            'endereco'             => 'Endereço a informar.',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 37
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Gessica Camila da Silva',
            'nome_estabelecimento' => 'Clínica Gessica Camila Podologia',
            'endereco'             => 'Avenida XV de Novembro, 104',
            'cnaes'                => '8690-9/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-02/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-02-02',
            'vencimento_alvara'  => '2027-02-02',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 38
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Patrícia Figueiredo',
            'nome_estabelecimento' => 'Clínica Patrícia Figueiredo Odontologia',
            'endereco'             => 'Rua Paraná, 47 - Centro',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-08/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-08-01',
            'vencimento_alvara'  => '2026-08-01',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 39
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Igor Ferreira Botto',
            'nome_estabelecimento' => 'Clinica Revigore Centro Médico e Exames EIRELLISILVA LTDA.',
            'endereco'             => 'Rua Parana, 97',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-03/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-03-20',
            'vencimento_alvara'  => '2026-03-20',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 40
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maik',
            'nome_estabelecimento' => 'Clube Social Ilicinense',
            'endereco'             => 'Rua Edson Bernardesa Vilela, 301',
            'cnaes'                => '9312-3/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-04/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-04-24',
            'vencimento_alvara'  => '2026-04-24',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 41
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Juliana Aparecida Alves Faria',
            'nome_estabelecimento' => 'Comercial Faria LTDA.',
            'endereco'             => 'Rua Maranhão, 171',
            'cnaes'                => '4712-1/00 4722-9/01 4724-5/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-01/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-01-14',
            'vencimento_alvara'  => '2027-01-14',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 42
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Danele Françue Vitar Lima',
            'nome_estabelecimento' => 'Consultorio Danelly Lima',
            'endereco'             => 'Praça Padre João Lourenço Leite, 47',
            'cnaes'                => '8650-0/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-01/2026',
            'nivel_risco'        => '1',
            'data_alvara'        => '2026-01-19',
            'vencimento_alvara'  => null,
            'contato'            => '(35) 98473-2569',
            'status'             => 'Não requerido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 43
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Izabelly Lucarini Vilela Carvalho',
            'nome_estabelecimento' => 'Consultório Izabelle Lucarini',
            'endereco'             => 'Avenida XV de Novembro, 55',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-12/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-12-18',
            'vencimento_alvara'  => '2026-12-18',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 44
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Frank Luis Martins Graneiro CRM/MG 38485',
            'nome_estabelecimento' => 'Consultório Médico Dr. Frank',
            'endereco'             => 'Avenida Xv de Novembro, 155',
            'cnaes'                => '8630-5/03 8610-1/02 8630-5/01 8630-5/02 8640-2/05 8650-0/05 8650-0/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-01/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2026-01-28',
            'vencimento_alvara'  => '2027-01-28',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 45
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Aluany Luisa de Sousa Alvarenga',
            'nome_estabelecimento' => 'Consultório Odontológico Aluany',
            'endereco'             => 'Rua Áurea, 670',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-09/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-09-11',
            'vencimento_alvara'  => '2026-09-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 46
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Angelo de Oliveira Junior Dias',
            'nome_estabelecimento' => 'Consultório Odontológico Angelo',
            'endereco'             => 'Avenida 15 de Novembro, 8 - Centro',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-07/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-07-11',
            'vencimento_alvara'  => '2026-07-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 47
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Gabriela Alvarenga Vilela',
            'nome_estabelecimento' => 'Consultório Odontológico Gabriela',
            'endereco'             => 'Rua Direita, 34',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-06/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-06-30',
            'vencimento_alvara'  => '2026-06-30',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 48
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Gisele Damasceno',
            'nome_estabelecimento' => 'Consultório Odontológico Gisele',
            'endereco'             => 'Rua Francisco de Ouro, 104',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-05/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-04-27',
            'vencimento_alvara'  => '2026-04-27',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 49
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Gisele Arco Verde',
            'nome_estabelecimento' => 'Consultório Odontológico Giséle Arco Verde',
            'endereco'             => 'Rua Boa Esperança, 267',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-07/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-07-15',
            'vencimento_alvara'  => '2026-07-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 50
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Regina Oliveira Silva',
            'nome_estabelecimento' => 'Consultório Odontológico RG',
            'endereco'             => 'Rua do Comércio, 116 - Centro',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-07/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-07-28',
            'vencimento_alvara'  => '2026-07-28',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 51
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Viviane Vilela Oliveira',
            'nome_estabelecimento' => 'Consultório Odontológico Viviane',
            'endereco'             => 'Rua Direita, 85',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-05/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-05-25',
            'vencimento_alvara'  => '2026-05-25',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 52
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ciciana Almeida Silva',
            'nome_estabelecimento' => 'CRAS (centro de assistência)',
            'endereco'             => 'Avenida 15 de novembro, 690',
            'cnaes'                => '8730-1/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-03/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-03-25',
            'vencimento_alvara'  => '2026-03-25',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 53
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Helena Carvalho',
            'nome_estabelecimento' => 'Creche Cemei Luriane Rosalves Ribeiro',
            'endereco'             => 'Rua Querubino Vilela Moscardini, 210',
            'cnaes'                => '8411-2/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-03/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-03-31',
            'vencimento_alvara'  => '2026-03-31',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 54
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Cristiane de Paula Silva',
            'nome_estabelecimento' => 'Cristiane Bolos',
            'endereco'             => 'Zona Rural Comunidade Grotão',
            'cnaes'                => '5611-2/03 5620-1/04 4721-1/03 1092-9/00 1091-1/01 4724-5/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-02/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-02-25',
            'vencimento_alvara'  => '2027-02-25',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 55
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Gabriel Henrique da Cunha',
            'nome_estabelecimento' => 'Distibuidora Conveniencia SM.',
            'endereco'             => 'Avenida Jose Vilela da Costa, 204',
            'cnaes'                => '4723-7/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 56
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Rafael Oliveira Damasceno',
            'nome_estabelecimento' => 'Docê Hamburgueria e Confeitaria',
            'endereco'             => 'Rua Santa Catarina, 70 - Centro',
            'cnaes'                => '5611-2/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 57
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Otávio Henrique Rezende A.',
            'nome_estabelecimento' => 'Droga 100',
            'endereco'             => 'Rua Comércio, 855',
            'cnaes'                => '4771-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 58
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Daine Aparecida Lima',
            'nome_estabelecimento' => 'Drogaria Dias - ME (Filial)',
            'endereco'             => 'Praça Sete de Setembro, 50',
            'cnaes'                => '4471-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-02/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2026-02-03',
            'vencimento_alvara'  => '2027-02-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 59
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Jaqueline Dayane da Silva CRF/MG31.695',
            'nome_estabelecimento' => 'Drogaria Dias Ltda. Rede Mais Minas (Matriz)',
            'endereco'             => 'Avenida José Vilela da Costa, 514',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 60
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Denis Rafael Vilela de Sousa CRF 44623',
            'nome_estabelecimento' => 'Drogaria Minas Master (Filial)',
            'endereco'             => 'Avenida 15 de Novembro, 316',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 61
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Deliani Aparecida Oliveira',
            'nome_estabelecimento' => 'Drogaria Minas Master (Matriz)',
            'endereco'             => 'Rua Do Comércio, 111',
            'cnaes'                => '4771-7/01-02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 62
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Otávio Junqueira Silva',
            'nome_estabelecimento' => 'Drogaria Minas Super Farma',
            'endereco'             => 'Avenida 15 de Novembro, 300',
            'cnaes'                => '4771-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-04/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-05-21',
            'vencimento_alvara'  => '2026-05-21',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 63
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Marilia Ranieli de Sousa',
            'nome_estabelecimento' => 'Drogaria Nossa Senhora Aparecida',
            'endereco'             => 'Rua Boa Esperança, 788',
            'cnaes'                => '4771-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 64
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Mateus Faustino de Carvalho',
            'nome_estabelecimento' => 'Drogaria Nossa Senhora Aparecida (Filial)',
            'endereco'             => 'Rua Francisco de Ouro, 90',
            'cnaes'                => '4771-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 65
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Adriano da Silva Pires',
            'nome_estabelecimento' => 'EDSON SILVA BORGES (Funerária Santa Rita)',
            'endereco'             => 'Rua 2 de novembro, 112',
            'cnaes'                => '9603-3/04 4789-0/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-11/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-11-03',
            'vencimento_alvara'  => '2026-11-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 66
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Andrelisa Aparecida de Andrade Carrijo',
            'nome_estabelecimento' => 'EMPORIO J.N LTDA',
            'endereco'             => 'Rua Maranhão, 03',
            'cnaes'                => '4712-1/00 5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-01',
            'vencimento_alvara'  => '2026-09-01',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 67
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Claudia Aparecida Nunes',
            'nome_estabelecimento' => 'Escola Estadual Nossa Senhora Aparecida',
            'endereco'             => 'Rua Doze de Outúbro, 198',
            'cnaes'                => '8513-9',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '14-03/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-03-31',
            'vencimento_alvara'  => '2026-03-31',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 68
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Elaine Carvalho',
            'nome_estabelecimento' => 'Escola Municiapal Maria Elma Firmino',
            'endereco'             => 'Praça do Rosario,',
            'cnaes'                => '8412-4/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-03/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-03-27',
            'vencimento_alvara'  => '2026-03-27',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 69
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Izabel Aparecida Oliveira',
            'nome_estabelecimento' => 'Escola Municipal Neiva Maria Mendes',
            'endereco'             => 'Rua Aurea, 448',
            'cnaes'                => '8412-4/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 70
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Germana Isabel Misseno',
            'nome_estabelecimento' => 'Escola Municipal Professor Ismael Silva',
            'endereco'             => 'Rua Francisco Augusto Passos Maia, 120',
            'cnaes'                => '8412-4/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-03/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-03-25',
            'vencimento_alvara'  => '2026-03-25',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 71
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Vera Lucia Marciano',
            'nome_estabelecimento' => 'Espaço Diola',
            'endereco'             => 'Rodovia Br 265, km 0',
            'cnaes'                => '8230-0/01 8230-0/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-01/2026',
            'nivel_risco'        => '1',
            'data_alvara'        => '2026-01-19',
            'vencimento_alvara'  => null,
            'contato'            => '(35) 9 8438-0778',
            'status'             => 'Não requerido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 72
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Bruniely Aparecida Belineli',
            'nome_estabelecimento' => 'Estética Bruniely',
            'endereco'             => 'Rua Aurea, 70 - Centro',
            'cnaes'                => '9602-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-07/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-07-01',
            'vencimento_alvara'  => '2026-07-01',
            'contato'            => '(35) 9 8469-0507',
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 73
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Erica Fernanda de Paulo',
            'nome_estabelecimento' => 'Estética Erica Fernanda',
            'endereco'             => 'Rua do Comércio, 111 - sala 01',
            'cnaes'                => '9602-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-06/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-06-03',
            'vencimento_alvara'  => '2026-06-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 74
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Lilian Oliveira Dutra Costa',
            'nome_estabelecimento' => 'Farmácia de Minas',
            'endereco'             => 'Rua 12 do Outubro,  345',
            'cnaes'                => '4771-7/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '14-01/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2026-01-30',
            'vencimento_alvara'  => '2027-01-30',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 75
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Dijan Vitor Freire',
            'nome_estabelecimento' => 'Festa do Peão de Ilicínea',
            'endereco'             => 'Centro de Eventos Rodolfo Bernardes Ferreira',
            'cnaes'                => '8230-0/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 76
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Evani Alvarenga Vilela',
            'nome_estabelecimento' => 'Festa Junina Creche',
            'endereco'             => 'Rua Dois de Novembro',
            'cnaes'                => '8230-0/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 77
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Naiane Fonseca',
            'nome_estabelecimento' => 'Festa Junina na Praça',
            'endereco'             => 'Praça Padre João Lourenço Leite',
            'cnaes'                => '8230-0/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 78
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Lucilei Dias Rocha',
            'nome_estabelecimento' => 'Hortigranjeiro',
            'endereco'             => 'Praça Padre João Lourenço Leite',
            'cnaes'                => '4724-5/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-04/2025',
            'nivel_risco'        => '1',
            'data_alvara'        => '2025-04-08',
            'vencimento_alvara'  => null,
            'contato'            => null,
            'status'             => 'Não requerido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 79
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Aliny Bruniely Amaral',
            'nome_estabelecimento' => 'Instituição de Longa Permanência para Idosos Vila Vicentina',
            'endereco'             => 'Rua São Vicente, 100',
            'cnaes'                => '8711-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-08/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-08-15',
            'vencimento_alvara'  => '2026-08-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 80
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Jaqueline Alves',
            'nome_estabelecimento' => 'Jaqueline Alves',
            'endereco'             => 'Rua do Comércio, 1050',
            'cnaes'                => '8650-0/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-09/2025',
            'nivel_risco'        => '1',
            'data_alvara'        => '2025-09-25',
            'vencimento_alvara'  => null,
            'contato'            => null,
            'status'             => 'Não requerido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 81
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Joyce Maria Leopoldino',
            'nome_estabelecimento' => 'JOYCE MARIA LEOPOLDINO (Banho e tosa)',
            'endereco'             => 'Rua Áurea, 417',
            'cnaes'                => '9609-2/08',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 82
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Fabricio Faria de Oliveira',
            'nome_estabelecimento' => 'KAROLAINE APARECIDA CASSIANO OLIVEIRA (Mascote)',
            'endereco'             => 'Rua Brasil, 407',
            'cnaes'                => '4712-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 83
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ricardo Luiz Pereira Sergio',
            'nome_estabelecimento' => 'Kspetos Bar - Ricardo do Espeto',
            'endereco'             => 'Avenida XV de Novembro, 1240',
            'cnaes'                => '5611-2/04 4723-7/00 5611-2/01 5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 84
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Waldir José de Assis',
            'nome_estabelecimento' => 'Laboratório Biológica',
            'endereco'             => 'Rua Coqueiral, 10',
            'cnaes'                => '8640-2/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '18-08/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-08-29',
            'vencimento_alvara'  => '2026-08-29',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 85
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'João Marcos Siqueira',
            'nome_estabelecimento' => 'Lanchonete D`Guste',
            'endereco'             => 'Rua Brasil, 03',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-03/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-03-10',
            'vencimento_alvara'  => '2026-03-10',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 86
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ana Paula Alves de Assis',
            'nome_estabelecimento' => 'Lanchonete Espaço Burger',
            'endereco'             => 'Rua Sebastião Cardoso, 71',
            'cnaes'                => '5611-2/03 5620-1/04 4723-7/00 1033-3/01 4729-6/99 4721-1/02 1091-1/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 87
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Laís Correia Araújo',
            'nome_estabelecimento' => 'Lanchonete House Burger',
            'endereco'             => 'Avenida José Vilela da Costa, 325',
            'cnaes'                => '5611-2/03 4729-6/99 5320-2/02 5611-2/01 5611-2/04 5620-1/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 88
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Claudio Cesar Vaneli',
            'nome_estabelecimento' => 'Lanchonete Los Vaneli',
            'endereco'             => 'Rua Boa Esperança, 526',
            'cnaes'                => '5611-2/03 4729-6/99 5611-2/01 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 89
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Luiz Henrique Pereira Sergio',
            'nome_estabelecimento' => 'Lanchonete Mega Lanche',
            'endereco'             => 'Rua Boa Esperança, 156',
            'cnaes'                => '5611-2/03 5611-2/01 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 90
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Letícia Sabrino Francisco/ Hudson Ramos da Cunha',
            'nome_estabelecimento' => 'Lanchonete Mek Dog e Açai',
            'endereco'             => 'Rua Joaquim Marciano de Carvalho',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 91
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Neymar Pereira Guedes',
            'nome_estabelecimento' => 'Lanchonete P. Café da Praça',
            'endereco'             => 'Praça Padre João Lourenço Leite, 21A',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 92
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Tallys Bricio Morais Silva',
            'nome_estabelecimento' => 'Lanchonete Sabor na Chapa',
            'endereco'             => 'Praça Padre João Lourenço Leite, 212',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 93
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Livia Aparecida de Souza',
            'nome_estabelecimento' => 'Lanconete THE FRUIT',
            'endereco'             => 'Praça 7 de setembro, 51 Loja03',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 94
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Adenilson Ferreira',
            'nome_estabelecimento' => 'Mercado Adenilson',
            'endereco'             => 'Rua Doze de Outúbro, 116',
            'cnaes'                => '4711-3/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '15-04/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-04-29',
            'vencimento_alvara'  => '2026-04-29',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 95
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Antônio José Martins Murta',
            'nome_estabelecimento' => 'Mercado CARLINHOS SUPERMERCADOS LTDA',
            'endereco'             => 'Rua Salvador Procópio, 290',
            'cnaes'                => '4711-3/02 4722-9/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '16-08/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-08-22',
            'vencimento_alvara'  => '2026-08-22',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 96
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Luciano Francisco de Assis',
            'nome_estabelecimento' => 'Mercado e Bar do Luciano',
            'endereco'             => 'Rua Tiradentes, 840',
            'cnaes'                => '4712-1/00 4721-1/02 4722-9/01 4723-7/00 4724-5/00 5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-11/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-11-25',
            'vencimento_alvara'  => '2026-11-25',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 97
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Carlos Vilela',
            'nome_estabelecimento' => 'Mercado Matheus Thieres',
            'endereco'             => 'Avenida José Vilela da Costa',
            'cnaes'                => '4711-3/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-07/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-07-09',
            'vencimento_alvara'  => '2026-07-09',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 98
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Stefani Cristina Silva',
            'nome_estabelecimento' => 'Mercado Padre Vitor - Filial',
            'endereco'             => 'Rua Boa Esperança, 340',
            'cnaes'                => '4711-3/02 4722-9/01 4721-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-09',
            'vencimento_alvara'  => '2026-09-09',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 99
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Lucimara Amaral Oliveira',
            'nome_estabelecimento' => 'Mercado Padre Vitor (Matriz)',
            'endereco'             => 'Rua do Comércio, 582',
            'cnaes'                => '4711-3/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-02/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-11',
            'vencimento_alvara'  => '2026-02-11',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 100
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Wendel Rodrigo Alves Pinheiro',
            'nome_estabelecimento' => 'Mercado Pinheiro',
            'endereco'             => 'Avenida 15 de Novembro, 675 - Gloria',
            'cnaes'                => '4712-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '25-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-30',
            'vencimento_alvara'  => '2026-07-30',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 101
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Luzia de Lourdes Vilela',
            'nome_estabelecimento' => 'Mercado Rainha da Paz',
            'endereco'             => 'Avenida 15 de Novembro - Gloria',
            'cnaes'                => '4712-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-03',
            'vencimento_alvara'  => '2026-07-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 102
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Lúcia Vilela Damasceno',
            'nome_estabelecimento' => 'Mercado São Geraldo',
            'endereco'             => 'Rua Doze de Outubro',
            'cnaes'                => '4772-5/00 4711-3/02 4722-9/01 4724-5/00 4723-7/00 4721-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '11-02/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2026-02-19',
            'vencimento_alvara'  => '2027-02-19',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 103
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Israel Benedito da Silva',
            'nome_estabelecimento' => 'Mercearia do Israel',
            'endereco'             => 'Rua Arildo Vilela Moscardine, 346',
            'cnaes'                => '4712-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-08/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-08-15',
            'vencimento_alvara'  => '2026-08-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 104
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Silvia Regina Resende da Silva',
            'nome_estabelecimento' => 'Mini Padaria da Silva',
            'endereco'             => 'Avenida Xv de Novembro, 580',
            'cnaes'                => '1091-1/02 4721-1/03 4721-1/04 4723-7/00 4729-6/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-01/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-01-21',
            'vencimento_alvara'  => '2027-01-21',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 105
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Marcelo Montijo Teixeira',
            'nome_estabelecimento' => 'Montijo Odontologia LTDA',
            'endereco'             => 'Rua do CoMÉRCIO, 465',
            'cnaes'                => '8635-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 106
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Núbia Alves Belineli',
            'nome_estabelecimento' => 'Movimento e Saúde',
            'endereco'             => 'Rua Boa Esperança, 470 - Centro',
            'cnaes'                => '8650-0/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-12/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-12-22',
            'vencimento_alvara'  => '2026-12-22',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 107
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Janizia Adriana Silva',
            'nome_estabelecimento' => 'Mercado Jc Faria Comercio de Alimentos LTDA.',
            'endereco'             => 'Rua do Comercio, 322',
            'cnaes'                => '4711-3/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-02/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-24',
            'vencimento_alvara'  => '2026-02-24',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 108
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Larissa Borges Costa',
            'nome_estabelecimento' => 'Oliva Granel',
            'endereco'             => 'Rua Santa Catarina,  42',
            'cnaes'                => '4729-6/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-08/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-08-05',
            'vencimento_alvara'  => '2026-08-05',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 109
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Fabrício Figueiredo Mendes',
            'nome_estabelecimento' => 'Ortoclínicas',
            'endereco'             => 'Rua do Comércio, 141 - Centro',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '11-07/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-07-15',
            'vencimento_alvara'  => '2026-07-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 110
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ana Clara Silva Nunes',
            'nome_estabelecimento' => 'Padaria Ana Clara',
            'endereco'             => 'Rua Oiapoque, 122',
            'cnaes'                => '4721-1/02 5620-1/04 5620-1/03 4729-6/99 4724-5/00 5611-2/01  5611-2/04 5611-2/03 1091-1/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '02-03/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-03-03',
            'vencimento_alvara'  => '2027-03-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 111
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Edivânia Aparecida Pereira',
            'nome_estabelecimento' => 'Padaria Bagueteria Ilicínea',
            'endereco'             => 'Rua Direita, 20 - Centro',
            'cnaes'                => '4721-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-17/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-07-15',
            'vencimento_alvara'  => '2026-07-15',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 112
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria de Fatima Oliveira',
            'nome_estabelecimento' => 'Padaria da Fatinha',
            'endereco'             => 'Rua Boa Esperança, 112',
            'cnaes'                => '1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 113
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Sueli Andrade Alves',
            'nome_estabelecimento' => 'Padaria da Sueli',
            'endereco'             => 'Rua Brasil, 756',
            'cnaes'                => '1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 114
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ricardo Alberto Fernandes',
            'nome_estabelecimento' => 'Padaria do Ricardo',
            'endereco'             => 'Rua do Comércio, 306',
            'cnaes'                => '4721-1/02 4712-1/00 4789-0/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 115
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Antônio Marcos Durval',
            'nome_estabelecimento' => 'Padaria Pão e CIA',
            'endereco'             => 'Rua Aurea, 892',
            'cnaes'                => '5611-2/03 4729-6/99 1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-01/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-01-29',
            'vencimento_alvara'  => '2027-01-29',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 116
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Neimar Pereira Guedes',
            'nome_estabelecimento' => 'Pães e Bolos Central',
            'endereco'             => 'Rua Direita, 72 - Centro',
            'cnaes'                => '1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 117
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Paula de Oliveira Figueiredo Lima',
            'nome_estabelecimento' => 'Pastelaria da Paula',
            'endereco'             => 'Avenida XV de novembro, 140',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-02/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-02-03',
            'vencimento_alvara'  => '2027-02-03',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 118
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Christian Luis Gois de Melo',
            'nome_estabelecimento' => 'Pastelaria do Christião',
            'endereco'             => 'Avenida José Vilela da Costa, 211',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 119
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maikel Pereira Sérgio',
            'nome_estabelecimento' => 'Pastelaria Rei do Salgado',
            'endereco'             => 'Rua Direita, 81 - Centro',
            'cnaes'                => '5611/2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 120
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Patrícia Magalhães',
            'nome_estabelecimento' => 'Patricciah M Studio Personal',
            'endereco'             => 'Rua 12 de Outúbro, 44 - Centro',
            'cnaes'                => '9313-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 121
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'A informar',
            'nome_estabelecimento' => 'Patty Odontologia',
            'endereco'             => 'Endereço a informar.',
            'cnaes'                => '00.00-0/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 122
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Pedro Domiciano Vieira',
            'nome_estabelecimento' => 'Pedro do Zeca',
            'endereco'             => 'Rua do Comércio, 530',
            'cnaes'                => '4712-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 123
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Markeline Garcia Silva',
            'nome_estabelecimento' => 'Peixaria Empório do Peixe',
            'endereco'             => 'Avenida José Vilela da Costa',
            'cnaes'                => '4722-9/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '07-06/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-06-17',
            'vencimento_alvara'  => '2026-06-17',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 124
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Leonice Aparecida Souza Oliveira',
            'nome_estabelecimento' => 'Peixaria Santa Maria',
            'endereco'             => 'Avenida José Vilela da Costa, 133',
            'cnaes'                => '4722-9/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-03/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-03-10',
            'vencimento_alvara'  => '2026-03-10',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 125
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Rozimeire Aparecida Andrade Rezende',
            'nome_estabelecimento' => 'Pizzaria Bella Italia',
            'endereco'             => 'Avenida XV de Novembro, 479',
            'cnaes'                => '5611-2/01 5611-2/03 5620-1/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '11-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-30',
            'vencimento_alvara'  => '2026-09-30',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 126
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Henrique Vilela Amaral',
            'nome_estabelecimento' => 'Polpa de frutas',
            'endereco'             => 'Rua Doze de Outubro, 371',
            'cnaes'                => '1031-7/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-06/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-01-27',
            'vencimento_alvara'  => '2026-01-27',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 127
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Paulo Gonçalves do Amaral',
            'nome_estabelecimento' => 'Polpa de frutas',
            'endereco'             => 'Rua Doze de Outubro, 371',
            'cnaes'                => '1031-7/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '10-01/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-01-27',
            'vencimento_alvara'  => '2026-01-27',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 128
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Marcio Vinícius Vilela',
            'nome_estabelecimento' => 'Posto de Coleta Messora e Vilela Ltda.',
            'endereco'             => 'Rua do Comércio, 40',
            'cnaes'                => '8640-2/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-01/2026',
            'nivel_risco'        => '3',
            'data_alvara'        => '2026-01-21',
            'vencimento_alvara'  => '2027-01-21',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 129
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Alda de Oliveira',
            'nome_estabelecimento' => 'PSF Central',
            'endereco'             => 'Rua Francisco de Ouro, 50',
            'cnaes'                => '8411-6/00 8690-9/99 8630-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-10/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-10-14',
            'vencimento_alvara'  => '2026-10-14',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 130
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Beatriz Aparecida Alves',
            'nome_estabelecimento' => 'PSF Rosário',
            'endereco'             => 'Rua Ly de Oliveira, 06',
            'cnaes'                => '8411-6/00 869-9/99 8630-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '05-10/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-10-05',
            'vencimento_alvara'  => '2026-10-05',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 131
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Gisele Aparecida de Carvalho Bueno',
            'nome_estabelecimento' => 'PSF Rural',
            'endereco'             => 'Praça do Rosario, 09',
            'cnaes'                => '8411-6/00 8690-9/99 8630-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-10/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-10-13',
            'vencimento_alvara'  => '2026-10-13',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 132
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Josyane Costa Conde Ferreira',
            'nome_estabelecimento' => 'Restaurante D`Guste',
            'endereco'             => 'Rua do Comércio, 665',
            'cnaes'                => '5611-2/11',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '08-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-12',
            'vencimento_alvara'  => '2026-02-12',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 133
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Maria Aparecida Alves',
            'nome_estabelecimento' => 'Restaurante da Nenem',
            'endereco'             => 'Praça Padre João Loureço Leite, 15',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-03/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-02-01',
            'vencimento_alvara'  => '2026-02-01',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 134
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Emerson Luiz de Souza',
            'nome_estabelecimento' => 'Restaurante disk Rango',
            'endereco'             => 'Rua Boa Esperança',
            'cnaes'                => '5611-2/01 5320-2/02 4723-7/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-08',
            'vencimento_alvara'  => '2026-10-08',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 135
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Tayrine Oliveira',
            'nome_estabelecimento' => 'Restaurante e Lanchonete Sabor Nobre',
            'endereco'             => 'Rua do Comércio, 270 - Centro',
            'cnaes'                => '5611-2/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '16-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-17',
            'vencimento_alvara'  => '2026-07-17',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 136
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Régia de Fátima Machado',
            'nome_estabelecimento' => 'Restaurante e Pousada Recanto dos Araças',
            'endereco'             => 'Rua Goiás, 15',
            'cnaes'                => '5590-6/99 5620-1/04 8230-0/02 5611-2/01 1091-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '12-01/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-31',
            'vencimento_alvara'  => '2026-10-31',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 137
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Taíza Amanda Rodrigues',
            'nome_estabelecimento' => 'Restaurante Porcolino Grill',
            'endereco'             => 'Avenida 15 de Novembro, 1721 - Monsenhor Francisco Figueiredo',
            'cnaes'                => '5611-2/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 138
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Claudia Aurica de Oliveira',
            'nome_estabelecimento' => 'Restaurante Tradição Mineira',
            'endereco'             => 'Rua Rita de Cassia, 48',
            'cnaes'                => '5611-2/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '09-06/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-06-24',
            'vencimento_alvara'  => '2026-06-24',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 139
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Adriana da Silveira Vitor',
            'nome_estabelecimento' => 'Restaurante Vitor/ Mathias Choperia',
            'endereco'             => 'Praça Padre João Lourenço Leite, 15',
            'cnaes'                => '5611-2/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-04/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-04-03',
            'vencimento_alvara'  => '2026-04-03',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 140
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Robson Moscardini',
            'nome_estabelecimento' => 'Robinho Dentista',
            'endereco'             => 'Praça Padre João Lourenço Leite, 9',
            'cnaes'                => '8630-5/04',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '03-08/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-08-05',
            'vencimento_alvara'  => '2026-08-05',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 141
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Karine de Freitas Silva',
            'nome_estabelecimento' => 'Salão beleza e estetica',
            'endereco'             => 'Rua Tiradentes, 02',
            'cnaes'                => '9602-5/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '11-02/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-02-18',
            'vencimento_alvara'  => '2026-02-18',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 142
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Conceição Aparecida Alves',
            'nome_estabelecimento' => 'Salão Cidinha Alves',
            'endereco'             => 'Rua Rita de Cássia, 101 - Centro',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '23-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-24',
            'vencimento_alvara'  => '2026-07-24',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 143
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ana Flávia Silvestre Silva',
            'nome_estabelecimento' => 'Salão da Ana Flávia',
            'endereco'             => 'Rua São Paulo, 212',
            'cnaes'                => '9602-5/01 4772-5/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-20',
            'vencimento_alvara'  => '2026-10-20',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 144
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Jaqueline Margaria de Lima',
            'nome_estabelecimento' => 'Salão da Jaque Lima',
            'endereco'             => 'Rua Direita, 104, Sala 16 - Centro',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '19-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-18',
            'vencimento_alvara'  => '2026-07-18',
            'contato'            => '(35) 9 9827-1912',
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 145
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Samara Santos da Silva',
            'nome_estabelecimento' => 'Salão da Samara',
            'endereco'             => 'Rua Direita, 104 - Centro',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '20-07/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-07-18',
            'vencimento_alvara'  => '2026-07-18',
            'contato'            => '(35) 9 8436-3659',
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 146
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Histefani Pereira Ferreira',
            'nome_estabelecimento' => 'Salão Espaço Renovar',
            'endereco'             => 'Rua Alfredo Pio, 203',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '01-01/2026',
            'nivel_risco'        => '2',
            'data_alvara'        => '2026-01-07',
            'vencimento_alvara'  => '2027-01-07',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 147
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Eduardo José de Oliveira',
            'nome_estabelecimento' => 'SAO BENTO COMERCIO E PRESTAÇOES DE SERVICO LTDA (Lava rápido, borracharia e oficina)',
            'endereco'             => 'Avenida XV de Novembro, 685',
            'cnaes'                => '4520-0/01 4520-0/03 4520-0/04 4520-0/05 4520-0/06 4520-0/07  4530-7/05 4543-9/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '04-09/2025',
            'nivel_risco'        => null,
            'data_alvara'        => '2025-09-11',
            'vencimento_alvara'  => '2026-09-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 148
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Adriana Miranda de Alcântara',
            'nome_estabelecimento' => 'Saúde Mental',
            'endereco'             => 'Avenida José Vilela da Costa, 565',
            'cnaes'                => '8720-4/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-09/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-09-22',
            'vencimento_alvara'  => '2026-09-22',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 149
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Vitor Tadeu Alves de Jesus',
            'nome_estabelecimento' => 'Sorveteria 4 Estações',
            'endereco'             => 'Avenida XV de Novembro, 565',
            'cnaes'                => '4729-6/99 5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '06-10/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-10-16',
            'vencimento_alvara'  => '2026-10-16',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 150
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Wallison Fernando de Oliveira',
            'nome_estabelecimento' => 'Sorveteria e Açai',
            'endereco'             => 'Praça 7 de setembro, 05',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 151
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Cleudes Oswaldo de Souza',
            'nome_estabelecimento' => 'Sorveteria Fino Sabor',
            'endereco'             => 'Rua do Comércio, 50',
            'cnaes'                => '4729-6/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 152
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Amanda Dias',
            'nome_estabelecimento' => 'Sorveteria PONTO 265',
            'endereco'             => 'Avenida 15 de Novembro',
            'cnaes'                => '4729-6/99',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 153
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Patrícia Conceição Alves Barbosa',
            'nome_estabelecimento' => 'Studio de Cabelos Phaty',
            'endereco'             => 'Rua Ituriel Viana Lemos, 45',
            'cnaes'                => '9602-5/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '11-08/2025',
            'nivel_risco'        => '2',
            'data_alvara'        => '2025-08-11',
            'vencimento_alvara'  => '2026-08-11',
            'contato'            => null,
            'status'             => 'Vigente',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 154
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Ailton Rodrigues da Cunha',
            'nome_estabelecimento' => 'Trailer do Ailton',
            'endereco'             => 'Praça Padre João Lourenço Leite/ Parque João A.',
            'cnaes'                => '5611-2/03',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 155
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Luis Fernando Mathias',
            'nome_estabelecimento' => 'Trem Bão Restaurante',
            'endereco'             => 'Praça Padre João Lourenço Leite, 15',
            'cnaes'                => '5611-2/01',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

        // Linha 156
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Eduardo Miranda de Carvalho',
            'nome_estabelecimento' => 'Veterinário e Pet Shop CENTER VET.',
            'endereco'             => 'Rua do Comércio, 770',
            'cnaes'                => '7500-1/00',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);
        DB::table('alvaras')->insertOrIgnore([
            'estabelecimento_id' => $estId,
            'numero_alvara'      => '13-04/2025',
            'nivel_risco'        => '3',
            'data_alvara'        => '2025-04-28',
            'vencimento_alvara'  => '2026-04-28',
            'contato'            => null,
            'status'             => 'Vencido',
            'created_at'         => $agora,
            'updated_at'         => $agora,
        ]);

        // Linha 157
        $estId = DB::table('estabelecimentos')->insertGetId([
            'nome_responsavel'     => 'Eandra Brunielli Oliveira Barbosa',
            'nome_estabelecimento' => 'Vita Care Serviços em Saúde',
            'endereco'             => 'Rua Dois de Novembro, 30 - Centro',
            'cnaes'                => '8610-1/02',
            'created_at'           => $agora,
            'updated_at'           => $agora,
        ]);

    }
}
