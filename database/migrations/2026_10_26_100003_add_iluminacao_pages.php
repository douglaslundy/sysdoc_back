<?php

use Database\Seeders\IluminacaoPageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new IluminacaoPageSeeder())->run();
    }

    public function down(): void
    {
        // Permissões saem em cascata com a página.
        DB::table('system_pages')->whereIn('path', array_column(IluminacaoPageSeeder::PAGINAS, 'path'))->delete();
        DB::table('page_categories')->where('nome', IluminacaoPageSeeder::CATEGORIA)->delete();
    }
};
