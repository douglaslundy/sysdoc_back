<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Agenda de viagens: passageiros/acompanhantes aguardando uma viagem numa data.
    // Ao serem enviados para uma viagem, as linhas são copiadas para trip_clients e apagadas daqui.
    public function up(): void
    {
        if (Schema::hasTable('trip_schedule_entries')) {
            return;
        }

        Schema::create('trip_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->date('scheduled_date')->index();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('person_type', ['passenger', 'companion'])->default('passenger');
            $table->unsignedBigInteger('companion_of')->nullable()->index();
            $table->string('phone', 20)->nullable();
            $table->string('departure_location', 50)->nullable();
            $table->string('destination_location', 50)->nullable();
            $table->string('time', 5)->nullable();
            $table->string('status', 20)->default('agendado');
            $table->string('obs', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_schedule_entries');
    }
};
