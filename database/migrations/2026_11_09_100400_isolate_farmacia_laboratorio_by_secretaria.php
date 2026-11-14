<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filhas sem coluna própria (herdam o pai): exame_campos, campo_referencias, pedido_exame_itens,
     * resultado_exames, resultado_campos.
     */
    private const TABELAS = [
        'medicine_items', 'medicine_daily_statuses', 'medicine_monthly_acquisitions', 'medicine_publications',
        'pharmacy_units', 'pharmacy_pharmaceutical_forms', 'pharmacy_presentations', 'pharmacy_acquisition_sources',
        'pharmacy_medicine_panel_settings',
        'exames', 'categoria_exames', 'medicos_solicitantes', 'lab_configs', 'pedidos_exame',
    ];

    /** tabela => [[coluna única antiga, nome do índice novo]]; a unicidade passa a valer por secretaria. */
    private const UNICOS = [
        'medicine_items' => [['internal_code', 'medicine_items_sec_code_unique']],
        'pharmacy_units' => [['code', 'pharmacy_units_sec_code_unique'], ['name', 'pharmacy_units_sec_name_unique']],
        'pharmacy_pharmaceutical_forms' => [['name', 'pharmacy_forms_sec_name_unique']],
        'pharmacy_presentations' => [['name', 'pharmacy_presentations_sec_name_unique']],
        'pharmacy_acquisition_sources' => [['name', 'pharmacy_sources_sec_name_unique']],
        'exames' => [['codigo', 'exames_sec_codigo_unique']],
        'categoria_exames' => [['nome', 'categoria_exames_sec_nome_unique']],
    ];

    /** Uma linha de configuração por secretaria. */
    private const CONFIGS = [
        'lab_configs' => 'lab_configs_secretaria_unique',
        'pharmacy_medicine_panel_settings' => 'pharmacy_panel_secretaria_unique',
    ];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);

        foreach (self::UNICOS as $tabela => $unicos) {
            foreach ($unicos as [$coluna, $novo]) {
                Schema::table($tabela, function (Blueprint $t) use ($coluna, $novo) {
                    $t->unique(['secretaria_id', $coluna], $novo);
                    $t->dropUnique([$coluna]);
                });
            }
        }

        foreach (self::CONFIGS as $tabela => $indice) {
            Schema::table($tabela, fn (Blueprint $t) => $t->unique('secretaria_id', $indice));
        }
    }

    public function down(): void
    {
        foreach (self::CONFIGS as $tabela => $indice) {
            IsolaPorSecretaria::dropFk($tabela);
            Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($indice));
        }

        foreach (self::UNICOS as $tabela => $unicos) {
            // O índice composto sustenta a FK de secretaria_id: solta a FK antes de trocar os índices.
            IsolaPorSecretaria::dropFk($tabela);
            foreach ($unicos as [$coluna, $novo]) {
                Schema::table($tabela, function (Blueprint $t) use ($coluna, $novo) {
                    $t->unique([$coluna]);
                    $t->dropUnique($novo);
                });
            }
        }

        IsolaPorSecretaria::down(self::TABELAS);
    }
};
