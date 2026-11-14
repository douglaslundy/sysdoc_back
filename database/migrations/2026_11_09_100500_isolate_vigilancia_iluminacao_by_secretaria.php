<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filhas sem coluna própria (herdam o pai): fiscalizacao_attachments, fiscalizacao_movimentacoes,
     * iluminacao_poste_fotos, iluminacao_poste_movimentacoes. `cnaes` (tabela oficial IBGE/CONCLA) e o
     * pivô `estabelecimento_cnaes` continuam globais. Protocolos FIS-/IP- derivam do id, já são únicos.
     */
    private const TABELAS = [
        'estabelecimentos', 'alvaras', 'fiscalizacoes', 'peticao_motivos',
        'vigilancia_configs', 'vigilancia_contatos_whatsapp', 'iluminacao_postes',
    ];

    /** tabela => [coluna única antiga, nome do índice antigo, nome do índice novo]; a unicidade passa a valer por secretaria. */
    private const UNICOS = [
        // Mantém o nome antigo: o AlvaraController reconhece a colisão de número por ele.
        'alvaras' => ['numero_alvara', 'alvaras_numero_alvara_unique', 'alvaras_numero_alvara_unique'],
        'peticao_motivos' => ['nome', 'peticao_motivos_nome_unique', 'peticao_motivos_sec_nome_unique'],
        'iluminacao_postes' => ['client_uuid', 'iluminacao_postes_client_uuid_unique', 'iluminacao_postes_sec_client_uuid_unique'],
    ];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);

        foreach (self::UNICOS as $tabela => [$coluna, $antigo, $novo]) {
            Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($antigo));
            Schema::table($tabela, fn (Blueprint $t) => $t->unique(['secretaria_id', $coluna], $novo));
        }

        // Uma configuração da Vigilância por secretaria.
        Schema::table('vigilancia_configs', fn (Blueprint $t) => $t->unique('secretaria_id', 'vigilancia_configs_secretaria_unique'));
    }

    public function down(): void
    {
        IsolaPorSecretaria::dropFk('vigilancia_configs');
        Schema::table('vigilancia_configs', fn (Blueprint $t) => $t->dropUnique('vigilancia_configs_secretaria_unique'));

        foreach (self::UNICOS as $tabela => [$coluna, $antigo, $novo]) {
            // O índice composto sustenta a FK de secretaria_id: solta a FK antes de trocar os índices.
            IsolaPorSecretaria::dropFk($tabela);
            Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($novo));
            Schema::table($tabela, fn (Blueprint $t) => $t->unique([$coluna], $antigo));
        }

        IsolaPorSecretaria::down(self::TABELAS);
    }
};
