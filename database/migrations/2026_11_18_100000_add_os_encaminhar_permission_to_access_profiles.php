<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Permissão do perfil: abrir OS para qualquer secretaria e encaminhar OS a outra secretaria/técnico. Começa desligada; admin sempre pode.
    public function up(): void
    {
        if (! Schema::hasTable('access_profiles') || Schema::hasColumn('access_profiles', 'os_encaminhar_enabled')) {
            return;
        }

        Schema::table('access_profiles', function (Blueprint $table) {
            $table->boolean('os_encaminhar_enabled')->default(false);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('access_profiles', 'os_encaminhar_enabled')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->dropColumn('os_encaminhar_enabled');
            });
        }
    }
};
