<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alvaras', function (Blueprint $table) {
            $table->string('status', 50)->default('Não requerido')->after('contato');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('alvaras', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
