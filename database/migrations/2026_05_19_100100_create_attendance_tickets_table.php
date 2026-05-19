<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number');
            $table->string('display_code', 20);
            $table->date('sequence_date');

            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients');

            $table->enum('status', [
                'aguardando',
                'chamada',
                'em_atendimento',
                'finalizada',
                'cancelada',
                'nao_compareceu',
            ])->default('aguardando');

            $table->dateTime('issued_at');
            $table->dateTime('called_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('no_show_at')->nullable();

            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->foreign('assigned_user_id')->references('id')->on('users');

            $table->unsignedBigInteger('room_id')->nullable();
            $table->foreign('room_id')->references('id')->on('attendance_rooms');

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users');

            $table->timestamps();

            $table->unique(['sequence_date', 'number'], 'attendance_tickets_unique_daily_number');
            $table->unique(['sequence_date', 'display_code'], 'attendance_tickets_unique_daily_code');

            $table->index('status');
            $table->index('client_id');
            $table->index('assigned_user_id');
            $table->index('room_id');
            $table->index('issued_at');
            $table->index('called_at');
            $table->index(['status', 'issued_at']);
            $table->index(['status', 'called_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_tickets');
    }
};
