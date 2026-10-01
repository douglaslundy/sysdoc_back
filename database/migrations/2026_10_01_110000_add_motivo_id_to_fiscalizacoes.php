<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscalizacoes') || Schema::hasColumn('fiscalizacoes', 'motivo_id')) {
            return;
        }

        Schema::table('fiscalizacoes', function (Blueprint $table) {
            $table->foreignId('motivo_id')->nullable()->after('origem')
                ->constrained('peticao_motivos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('fiscalizacoes', 'motivo_id')) {
            Schema::table('fiscalizacoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('motivo_id');
            });
        }
    }
};
