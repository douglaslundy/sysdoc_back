<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('raca_cor', 30)->nullable()->after('sexo');
            $table->date('data_obito')->nullable()->after('raca_cor');
            $table->boolean('st_falecido')->default(false)->after('data_obito');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['raca_cor', 'data_obito', 'st_falecido']);
        });
    }
};
