<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acesso às filas por especialidade, agora definido no PERFIL de acesso (antes era por usuário,
 * em user_speciality_permissions, que fica no banco sem uso). A tabela nasce vazia: todo perfil
 * (exceto admin, que sempre tem acesso) começa sem acesso a nenhuma especialidade.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profile_speciality_permissions')) {
            return;
        }

        Schema::create('profile_speciality_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_profile_id')->constrained('access_profiles')->cascadeOnDelete();
            $table->foreignId('speciality_id')->constrained('specialities')->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_insert')->default(false);
            $table->timestamps();

            $table->unique(['access_profile_id', 'speciality_id'], 'profile_speciality_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_speciality_permissions');
    }
};
