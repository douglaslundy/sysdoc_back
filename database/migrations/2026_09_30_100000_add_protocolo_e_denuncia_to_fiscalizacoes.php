<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscalizacoes')) {
            return;
        }

        if (! Schema::hasColumn('fiscalizacoes', 'protocolo')) {
            // Denúncia pública pode citar estabelecimento não cadastrado e ainda não tem visita.
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE fiscalizacoes MODIFY estabelecimento_id BIGINT UNSIGNED NULL, MODIFY data_visita DATE NULL');
            }

            Schema::table('fiscalizacoes', function (Blueprint $table) {
                $table->string('protocolo', 20)->nullable()->unique('fiscalizacoes_protocolo_unique');
                $table->string('origem', 20)->default('interna')->index('fiscalizacoes_origem_index');
                $table->string('assunto', 200)->nullable();
                $table->text('descricao_denuncia')->nullable();
                $table->string('local_endereco', 255)->nullable();
                $table->string('estabelecimento_nome_informado', 200)->nullable();
                $table->string('denunciante_nome', 150)->nullable();
                $table->string('denunciante_contato', 150)->nullable();
                $table->string('senha_consulta_hash', 255)->nullable();
            });
        }

        // Fiscalizações antigas ganham protocolo (idempotente: só as que ainda não têm).
        DB::statement(
            "UPDATE fiscalizacoes SET protocolo = CONCAT('FIS-', YEAR(created_at), '-', LPAD(id, 6, '0')) WHERE protocolo IS NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('fiscalizacoes') || ! Schema::hasColumn('fiscalizacoes', 'protocolo')) {
            return;
        }

        Schema::table('fiscalizacoes', function (Blueprint $table) {
            $table->dropUnique('fiscalizacoes_protocolo_unique');
            $table->dropIndex('fiscalizacoes_origem_index');
            $table->dropColumn([
                'protocolo', 'origem', 'assunto', 'descricao_denuncia', 'local_endereco',
                'estabelecimento_nome_informado', 'denunciante_nome', 'denunciante_contato', 'senha_consulta_hash',
            ]);
        });
    }
};
