<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Itens e histórico de requisição herdam a secretaria da requisição (sem coluna própria). */
    private const TABELAS = [
        'almoxarifado_secretarias', 'almoxarifado_categorias', 'almoxarifado_especies', 'almoxarifado_unidades_medida',
        'almoxarifado_fornecedores', 'almoxarifado_localizacoes', 'almoxarifado_produtos', 'almoxarifado_estoques',
        'almoxarifado_movimentacoes', 'almoxarifado_requisicoes', 'almoxarifado_configs',
    ];

    /** tabela => [coluna única antiga, nome do índice novo] ; a unicidade passa a valer por secretaria. */
    private const UNICOS = [
        'almoxarifado_secretarias' => ['nome', 'almox_secretarias_sec_nome_unique'],
        'almoxarifado_categorias' => ['nome', 'almox_categorias_sec_nome_unique'],
        'almoxarifado_especies' => ['nome', 'almox_especies_sec_nome_unique'],
        'almoxarifado_unidades_medida' => ['nome', 'almox_unidades_sec_nome_unique'],
        'almoxarifado_produtos' => ['codigo_interno', 'almox_produtos_sec_codigo_unique'],
        'almoxarifado_requisicoes' => ['numero', 'almox_requisicoes_sec_numero_unique'],
    ];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);

        foreach (self::UNICOS as $tabela => [$coluna, $novo]) {
            Schema::table($tabela, function (Blueprint $t) use ($coluna, $novo) {
                $t->unique(['secretaria_id', $coluna], $novo);
                $t->dropUnique([$coluna]);
            });
        }

        // Um registro de configuração por secretaria.
        Schema::table('almoxarifado_configs', fn (Blueprint $t) => $t->unique('secretaria_id', 'almox_configs_secretaria_unique'));
    }

    public function down(): void
    {
        Schema::table('almoxarifado_configs', function (Blueprint $t) {
            IsolaPorSecretaria::dropFk('almoxarifado_configs');
            $t->dropUnique('almox_configs_secretaria_unique');
        });

        foreach (self::UNICOS as $tabela => [$coluna, $novo]) {
            // O índice composto sustenta a FK de secretaria_id: solta a FK antes de trocar os índices.
            IsolaPorSecretaria::dropFk($tabela);
            Schema::table($tabela, function (Blueprint $t) use ($coluna, $novo) {
                $t->unique([$coluna]);
                $t->dropUnique($novo);
            });
        }

        IsolaPorSecretaria::down(self::TABELAS);
    }
};
