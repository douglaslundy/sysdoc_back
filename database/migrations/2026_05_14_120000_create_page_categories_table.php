<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->string('icone', 40)->nullable();
            $table->integer('ordem')->default(999);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('system_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('icone');
            $table->foreign('category_id')->references('id')->on('page_categories')->nullOnDelete();
        });

        $defaultCategories = [
            ['nome' => 'Geral', 'icone' => 'grid', 'ordem' => 1],
            ['nome' => 'Administração', 'icone' => 'shield', 'ordem' => 2],
            ['nome' => 'Cadastros', 'icone' => 'users', 'ordem' => 3],
            ['nome' => 'Laboratório', 'icone' => 'thermometer', 'ordem' => 4],
            ['nome' => 'Atendimento', 'icone' => 'activity', 'ordem' => 5],
            ['nome' => 'TFD', 'icone' => 'send', 'ordem' => 6],
            ['nome' => 'Documentos', 'icone' => 'file-text', 'ordem' => 7],
            ['nome' => 'Vigilância Sanitária', 'icone' => 'shield', 'ordem' => 8],
            ['nome' => 'Farmácia', 'icone' => 'package', 'ordem' => 9],
            ['nome' => 'Sistema', 'icone' => 'settings', 'ordem' => 10],
            ['nome' => 'Relatórios', 'icone' => 'bar-chart-2', 'ordem' => 11],
            ['nome' => 'Outros', 'icone' => 'grid', 'ordem' => 99],
        ];

        foreach ($defaultCategories as $cat) {
            DB::table('page_categories')->updateOrInsert(
                ['nome' => $cat['nome']],
                [
                    'icone' => $cat['icone'],
                    'ordem' => $cat['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $pages = DB::table('system_pages')->select('id', 'categoria')->get();
        foreach ($pages as $page) {
            if (! $page->categoria) {
                continue;
            }
            $categoryId = DB::table('page_categories')
                ->where('nome', $page->categoria)
                ->value('id');
            if ($categoryId) {
                DB::table('system_pages')->where('id', $page->id)->update(['category_id' => $categoryId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('system_pages', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('page_categories');
    }
};
