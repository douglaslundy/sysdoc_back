<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscalizacoes') || ! Schema::hasColumn('fiscalizacoes', 'origem')) {
            return;
        }

        DB::table('fiscalizacoes')->where('origem', 'denuncia')->update(['origem' => 'peticao']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fiscalizacoes') || ! Schema::hasColumn('fiscalizacoes', 'origem')) {
            return;
        }

        DB::table('fiscalizacoes')->where('origem', 'peticao')->update(['origem' => 'denuncia']);
    }
};
