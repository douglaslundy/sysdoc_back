<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'escolaridade')) {
                $table->string('escolaridade', 120)->nullable()->after('raca_cor');
            }
            if (!Schema::hasColumn('clients', 'nacionalidade')) {
                $table->string('nacionalidade', 120)->nullable()->after('escolaridade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'nacionalidade')) {
                $table->dropColumn('nacionalidade');
            }
            if (Schema::hasColumn('clients', 'escolaridade')) {
                $table->dropColumn('escolaridade');
            }
        });
    }
};

