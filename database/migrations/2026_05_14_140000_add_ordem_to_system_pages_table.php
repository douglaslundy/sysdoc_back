<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_pages', function (Blueprint $table) {
            $table->integer('ordem')->default(999)->after('category_id');
        });

        $pages = DB::table('system_pages')
            ->select('id', 'category_id', 'categoria', 'titulo')
            ->orderBy('category_id')
            ->orderBy('categoria')
            ->orderBy('titulo')
            ->get();

        $counter = [];
        foreach ($pages as $page) {
            $groupKey = $page->category_id ? ('cat:' . $page->category_id) : ('txt:' . ($page->categoria ?? 'Outros'));
            $counter[$groupKey] = ($counter[$groupKey] ?? 0) + 1;
            DB::table('system_pages')->where('id', $page->id)->update(['ordem' => $counter[$groupKey]]);
        }
    }

    public function down(): void
    {
        Schema::table('system_pages', function (Blueprint $table) {
            $table->dropColumn('ordem');
        });
    }
};

