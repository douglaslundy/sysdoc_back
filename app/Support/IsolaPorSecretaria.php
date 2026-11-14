<?php

namespace App\Support;

use App\Models\Secretaria;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrations de isolamento: secretaria_id nullable -> backfill Saúde (um UPDATE) -> NOT NULL + FK (restrict).
 * Reutiliza a coluna se outra task já a criou (protocols/kanban_tasks). MySQL.
 */
class IsolaPorSecretaria
{
    /** @param  string[]  $tabelas */
    public static function up(array $tabelas): void
    {
        foreach ($tabelas as $tabela) {
            if (! Schema::hasColumn($tabela, 'secretaria_id')) {
                Schema::table($tabela, fn (Blueprint $t) => $t->unsignedBigInteger('secretaria_id')->nullable());
            }

            if (DB::table($tabela)->whereNull('secretaria_id')->exists()) {
                $saude = Secretaria::where('slug', Secretaria::SAUDE_SLUG)->value('id');
                if (! $saude) {
                    throw new \RuntimeException("Secretaria Saúde ausente: não dá para preencher {$tabela}.secretaria_id.");
                }
                DB::table($tabela)->whereNull('secretaria_id')->update(['secretaria_id' => $saude]);
            }

            self::dropFk($tabela);
            DB::statement("ALTER TABLE `{$tabela}` MODIFY `secretaria_id` BIGINT UNSIGNED NOT NULL");
            Schema::table($tabela, function (Blueprint $t) {
                $t->foreign('secretaria_id')->references('id')->on('secretarias')->restrictOnDelete();
            });
        }
    }

    /** @param  string[]  $tabelas @param  string[]  $manterColuna tabelas cuja coluna existia antes (volta a nullable). */
    public static function down(array $tabelas, array $manterColuna = []): void
    {
        foreach ($tabelas as $tabela) {
            if (! Schema::hasColumn($tabela, 'secretaria_id')) {
                continue;
            }
            self::dropFk($tabela);

            if (in_array($tabela, $manterColuna, true)) {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY `secretaria_id` BIGINT UNSIGNED NULL");
                Schema::table($tabela, fn (Blueprint $t) => $t->foreign('secretaria_id')->references('id')->on('secretarias')->nullOnDelete());
            } else {
                Schema::table($tabela, fn (Blueprint $t) => $t->dropColumn('secretaria_id'));
            }
        }
    }

    public static function dropFk(string $tabela): void
    {
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME AS n FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$tabela, 'secretaria_id']
        );
        foreach ($fks as $fk) {
            Schema::table($tabela, fn (Blueprint $t) => $t->dropForeign($fk->n));
        }
    }
}
