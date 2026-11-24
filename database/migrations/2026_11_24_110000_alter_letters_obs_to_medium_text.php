<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Corpo do ofício passa a vir do editor rico (HTML): até 10.000 caracteres de texto, 60.000 de HTML.
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->mediumText('obs')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('obs', 3000)->nullable()->change();
        });
    }
};
