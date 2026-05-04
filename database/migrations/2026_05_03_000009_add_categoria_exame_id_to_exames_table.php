<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_exame_id')->nullable()->after('codigo');
            $table->foreign('categoria_exame_id')
                  ->references('id')->on('categoria_exames')
                  ->onDelete('set null');
            $table->dropColumn('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->dropForeign(['categoria_exame_id']);
            $table->dropColumn('categoria_exame_id');
            $table->string('categoria', 60)->nullable();
        });
    }
};
