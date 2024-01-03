<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('cpf');
            $table->dropColumn('mother');
            $table->dropColumn('father');
            $table->dropColumn('cns');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {            
            $table->string('cpf', 18)->nullable()->after('nome');            
            $table->string('mother', 100)->nullable()->after('nome');            
            $table->string('father', 100)->nullable()->after('mother');
            $table->string('cns', 15)->nullable()->after('father');
        });
    }
};