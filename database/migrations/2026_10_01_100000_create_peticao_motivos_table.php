<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('peticao_motivos')) {
            Schema::create('peticao_motivos', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 120)->unique();
                $table->string('descricao', 255)->nullable();
                // Unidade (estrutura do protocolo) responsável: recebe o card no kanban.
                $table->foreignId('unit_id')->nullable()
                    ->constrained('protocol_organizational_units')->nullOnDelete();
                $table->boolean('ativo')->default(true);
                $table->unsignedInteger('ordem')->default(0);
                $table->timestamps();

                $table->index(['ativo', 'ordem']);
            });
        }

        // Página de cadastro, para ser liberada em Perfis (idempotente).
        if (Schema::hasTable('system_pages') && ! DB::table('system_pages')->where('path', '/peticao-motivos')->exists()) {
            $row = [
                'titulo' => 'Motivos de Petição',
                'path' => '/peticao-motivos',
                'icone' => 'list',
                'categoria' => 'Vigilância Sanitária',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('system_pages', 'category_id') && Schema::hasTable('page_categories')) {
                $row['category_id'] = DB::table('page_categories')->where('nome', 'Vigilância Sanitária')->value('id');
            }
            DB::table('system_pages')->insert($row);
        }
    }

    public function down(): void
    {
        DB::table('system_pages')->where('path', '/peticao-motivos')->delete();
        Schema::dropIfExists('peticao_motivos');
    }
};
