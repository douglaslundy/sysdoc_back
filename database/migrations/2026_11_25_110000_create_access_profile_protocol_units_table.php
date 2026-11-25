<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('access_profile_protocol_units')) {
            return;
        }

        // Destinos de protocolo permitidos ao perfil (a unidade vale com as subunidades). Sem linhas = sem limite.
        Schema::create('access_profile_protocol_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_profile_id')->constrained('access_profiles', 'id', 'app_units_profile_fk')->cascadeOnDelete();
            $table->foreignId('protocol_organizational_unit_id')->constrained('protocol_organizational_units', 'id', 'app_units_unit_fk')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['access_profile_id', 'protocol_organizational_unit_id'], 'app_units_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_profile_protocol_units');
    }
};
