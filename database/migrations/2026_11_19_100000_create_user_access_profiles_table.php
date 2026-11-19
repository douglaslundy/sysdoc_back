<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfis de acesso ADICIONAIS do usuário. O perfil principal continua em users.profile;
 * as permissões efetivas são a união do principal com os adicionais.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_access_profiles')) {
            return;
        }

        Schema::create('user_access_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('access_profile_id')->constrained('access_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'access_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_profiles');
    }
};
