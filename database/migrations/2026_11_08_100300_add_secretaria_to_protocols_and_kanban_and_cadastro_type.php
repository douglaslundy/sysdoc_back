<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable de propósito: a retroalimentação e o NOT NULL ficam para a task de isolamento dos módulos.
        foreach (['protocols', 'kanban_tasks'] as $tabela) {
            if (! Schema::hasColumn($tabela, 'secretaria_id')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->foreignId('secretaria_id')->nullable()->constrained('secretarias')->nullOnDelete();
                });
            }
        }

        // Inativo: não aparece para escolha em protocolo comum (e a validação também o recusa).
        if (! DB::table('protocol_types')->where('codigo', 'solicitacao_cadastro')->exists()) {
            DB::table('protocol_types')->insert([
                'codigo' => 'solicitacao_cadastro',
                'nome' => 'Solicitação de cadastro',
                'descricao' => 'Pedido público de cadastro de usuário (visível só a administradores e gestores da secretaria).',
                'ordem' => 900,
                'ativo' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('protocol_types')->where('codigo', 'solicitacao_cadastro')->delete();

        foreach (['protocols', 'kanban_tasks'] as $tabela) {
            if (Schema::hasColumn($tabela, 'secretaria_id')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('secretaria_id');
                });
            }
        }
    }
};
